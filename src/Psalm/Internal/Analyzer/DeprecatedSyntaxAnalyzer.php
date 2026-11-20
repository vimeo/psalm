<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Codebase\PhpDeprecations;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\DeprecatedSyntax;
use Psalm\Issue\ReservedWord;
use Psalm\IssueBuffer;

use function array_pop;
use function end;
use function in_array;
use function strtolower;

/**
 * Reports the declarations and statements PHP deprecates, whatever PHP version is analysed: the names it reserves
 * (`let`, `is`, ...), `return` in `finally`, what `__construct()` and `__destruct()` return or yield, and a nullable
 * return type of `__debugInfo()`. A declaration's `@psalm-suppress` applies to what is reported inside it.
 *
 * @internal
 */
final class DeprecatedSyntaxAnalyzer extends NodeVisitorAbstract
{
    /** the names PHP 8.6 reserves for classes, interfaces, traits, enums, functions and constants */
    private const RESERVED_NAMES = ['let', 'is'];

    private string $namespace = '';

    /** @var list<?string> the classes being visited, null for an anonymous one */
    private array $classes = [];

    /**
     * @var list<array{string|null, int, bool}> the function-likes being visited: lowercase method name (null for a
     * function or a closure), how many finally blocks are open in it, whether it is a constructor or a destructor
     */
    private array $function_likes = [];

    /** @var list<array<int, string>> the issues suppressed in each declaration being visited, by offset */
    private array $suppressed_issues = [];

    /** the finally blocks open outside of any function-like */
    private int $top_level_finally_depth = 0;

    private function __construct(
        private readonly FileAnalyzer $file_analyzer,
        private readonly Codebase $codebase,
    ) {
    }

    /**
     * @param list<Stmt> $stmts
     */
    public static function analyze(FileAnalyzer $file_analyzer, Codebase $codebase, array $stmts): void
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new self($file_analyzer, $codebase));
        $traverser->traverse($stmts);
    }

    #[Override]
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Stmt\Namespace_) {
            $this->namespace = $node->name === null ? '' : $node->name->toString() . '\\';
        } elseif ($node instanceof Stmt\ClassLike) {
            $this->enterClassLike($node);
        } elseif ($node instanceof Stmt\ClassMethod) {
            $this->enterMethod($node);
        } elseif ($node instanceof Stmt\Function_) {
            $this->enterFunction($node);
        } elseif ($node instanceof Node\FunctionLike) {
            $this->function_likes[] = [null, 0, false];
        } elseif ($node instanceof Stmt\Finally_) {
            $this->enterFinally();
        } elseif ($node instanceof Stmt\Return_) {
            $this->checkReturn($node);
        } elseif ($node instanceof Yield_ || $node instanceof YieldFrom) {
            $this->checkYield($node);
        } elseif ($node instanceof Stmt\Const_) {
            foreach ($node->consts as $const) {
                $this->checkConstantName($const->name->name, $const);
            }
        } elseif ($node instanceof Stmt\ClassConst) {
            foreach ($node->consts as $const) {
                if (strtolower($const->name->name) === 'namespace') {
                    $this->report(new DeprecatedSyntax(
                        PhpDeprecations::getDeprecatedSinceMessage('A class constant named namespace', 8_06_00, null)
                            . ', rename it',
                        new CodeLocation($this->file_analyzer, $const),
                    ));
                }
            }
        } elseif ($node instanceof Stmt\Use_ || $node instanceof Stmt\GroupUse) {
            $this->checkUse($node);
        }

        return null;
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    public function leaveNode(Node $node): null
    {
        if ($node instanceof Stmt\ClassLike) {
            array_pop($this->classes);
            array_pop($this->suppressed_issues);
        } elseif ($node instanceof Stmt\ClassMethod || $node instanceof Stmt\Function_) {
            array_pop($this->function_likes);
            array_pop($this->suppressed_issues);
        } elseif ($node instanceof Node\FunctionLike) {
            array_pop($this->function_likes);
        } elseif ($node instanceof Stmt\Finally_) {
            $this->leaveFinally();
        }

        return null;
    }

    private function enterClassLike(Stmt\ClassLike $node): void
    {
        $fq_class_name = $node->name === null ? null : $this->namespace . $node->name->name;
        $this->classes[] = $fq_class_name;
        $this->suppressed_issues[] = $fq_class_name !== null && $this->codebase->classlike_storage_provider->has(
            strtolower($fq_class_name),
        )
            ? $this->codebase->classlike_storage_provider->get($fq_class_name)->suppressed_issues
            : [];

        if ($node->name !== null) {
            $kind = match (true) {
                $node instanceof Stmt\Interface_ => 'an interface',
                $node instanceof Stmt\Trait_ => 'a trait',
                $node instanceof Stmt\Enum_ => 'an enum',
                default => 'a class',
            };

            $this->checkReservedName($node->name->name, $kind, $node->name);
        }
    }

    private function enterMethod(Stmt\ClassMethod $node): void
    {
        $method_name = strtolower($node->name->name);
        $class = $this->classes === [] ? null : end($this->classes);
        $storage = $class !== null && $this->codebase->methods->hasStorage(new MethodIdentifier($class, $method_name))
            ? $this->codebase->methods->getStorage(new MethodIdentifier($class, $method_name))
            : null;

        $this->function_likes[] = [
            $method_name,
            0,
            $method_name === '__construct' || $method_name === '__destruct',
        ];
        $this->suppressed_issues[] = $storage?->suppressed_issues ?? [];

        if ($method_name === '__debuginfo' && $node->returnType !== null && self::isNullable($node->returnType)) {
            $this->report(new DeprecatedSyntax(
                PhpDeprecations::getDeprecatedSinceMessage(
                    'A nullable return type of __debugInfo()',
                    8_06_00,
                    null,
                ) . ', declare array',
                new CodeLocation($this->file_analyzer, $node->returnType),
            ));
        }
    }

    private function enterFunction(Stmt\Function_ $node): void
    {
        $function_id = strtolower($this->namespace . $node->name->name);

        $this->function_likes[] = [null, 0, false];
        $this->suppressed_issues[] = $this->codebase->file_storage_provider
            ->get($this->file_analyzer->getFilePath())
            ->functions[$function_id]
            ->suppressed_issues ?? [];

        $this->checkReservedName($node->name->name, 'a function', $node->name);

        if (strtolower($node->name->name) === 'readonly') {
            $this->report(new ReservedWord(
                PhpDeprecations::getDeprecatedSinceMessage('A function named readonly', 8_06_00, null) . ', rename it',
                new CodeLocation($this->file_analyzer, $node->name),
                $node->name->name,
            ));
        }
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function enterFinally(): void
    {
        $function_like = array_pop($this->function_likes);

        if ($function_like === null) {
            $this->top_level_finally_depth++;
        } else {
            $this->function_likes[] = [$function_like[0], $function_like[1] + 1, $function_like[2]];
        }
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function leaveFinally(): void
    {
        $function_like = array_pop($this->function_likes);

        if ($function_like === null) {
            $this->top_level_finally_depth--;
        } else {
            $this->function_likes[] = [$function_like[0], $function_like[1] - 1, $function_like[2]];
        }
    }

    private function checkReturn(Stmt\Return_ $node): void
    {
        $function_like = end($this->function_likes) ?: null;

        if (($function_like !== null ? $function_like[1] : $this->top_level_finally_depth) > 0) {
            $this->report(new DeprecatedSyntax(
                PhpDeprecations::getDeprecatedSinceMessage('A return in a finally block', 8_06_00, null)
                    . ': it discards the exception or the return value of the try block',
                new CodeLocation($this->file_analyzer, $node),
            ));
        }

        if ($node->expr !== null && $function_like !== null && $function_like[2]) {
            $this->report(new DeprecatedSyntax(
                PhpDeprecations::getDeprecatedSinceMessage(
                    'Returning a value from ' . $function_like[0] . '()',
                    8_06_00,
                    null,
                ),
                new CodeLocation($this->file_analyzer, $node),
            ));
        }
    }

    private function checkYield(Yield_|YieldFrom $node): void
    {
        $function_like = end($this->function_likes) ?: null;

        if ($function_like !== null && $function_like[2]) {
            $this->report(new DeprecatedSyntax(
                PhpDeprecations::getDeprecatedSinceMessage(
                    'Making ' . $function_like[0] . '() a generator',
                    8_06_00,
                    null,
                ),
                new CodeLocation($this->file_analyzer, $node),
            ));
        }
    }

    private function checkConstantName(string $name, Node $node): void
    {
        $this->checkReservedName($name, 'a constant', $node);

        if ($name === '_') {
            $this->report(new ReservedWord(
                PhpDeprecations::getDeprecatedSinceMessage('A constant named _', 8_06_00, null) . ', rename it',
                new CodeLocation($this->file_analyzer, $node),
                $name,
            ));
        }
    }

    private function checkUse(Stmt\Use_|Stmt\GroupUse $node): void
    {
        foreach ($node->uses as $use) {
            $type = $use->type === Stmt\Use_::TYPE_UNKNOWN ? $node->type : $use->type;
            $alias = $use->getAlias()->name;

            $this->checkReservedName($alias, 'an alias', $use);

            if ($alias === '_' && $type !== Stmt\Use_::TYPE_FUNCTION) {
                $this->report(new ReservedWord(
                    PhpDeprecations::getDeprecatedSinceMessage('An alias named _', 8_06_00, null) . ', rename it',
                    new CodeLocation($this->file_analyzer, $use),
                    $alias,
                ));
            }
        }
    }

    private function checkReservedName(string $name, string $kind, Node $node): void
    {
        if (in_array(strtolower($name), self::RESERVED_NAMES, true)) {
            $this->report(new ReservedWord(
                PhpDeprecations::getDeprecatedSinceMessage(
                    'Naming ' . $kind . ' ' . $name,
                    8_06_00,
                    null,
                ) . ', as ' . strtolower($name) . ' becomes a keyword',
                new CodeLocation($this->file_analyzer, $node),
                $name,
            ));
        }
    }

    /**
     * @psalm-mutation-free
     */
    private static function isNullable(Node $type): bool
    {
        if ($type instanceof Node\NullableType) {
            return true;
        }

        if ($type instanceof Node\UnionType) {
            foreach ($type->types as $member) {
                if ($member instanceof Node\Identifier && strtolower($member->name) === 'null') {
                    return true;
                }
            }
        }

        return false;
    }

    private function report(CodeIssue $issue): void
    {
        // the keys of docblock suppressions are their offsets, which mark them used
        $suppressed_issues = $this->file_analyzer->getSuppressedIssues();

        foreach ($this->suppressed_issues as $declaration_suppressed_issues) {
            $suppressed_issues += $declaration_suppressed_issues;
        }

        IssueBuffer::maybeAdd($issue, $suppressed_issues);
    }
}
