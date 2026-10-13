<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use Override;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\SourceAnalyzer;
use Psalm\Issue\ParseError;
use Psalm\IssueBuffer;

/**
 * Reports closures that PHP does not accept inside constant expressions (class/global constants,
 * property and parameter defaults, attribute arguments).
 *
 * @internal
 */
final class ConstExprClosureValidator extends NodeVisitorAbstract
{
    /**
     * @param array<array-key, string> $suppressed_issues
     */
    private function __construct(
        private readonly SourceAnalyzer $source,
        private readonly array $suppressed_issues,
    ) {
    }

    /**
     * @param array<array-key, string> $suppressed_issues
     */
    public static function validate(SourceAnalyzer $source, Node\Expr $expr, array $suppressed_issues): void
    {
        (new NodeTraverser(new self($source, $suppressed_issues)))->traverse([$expr]);
    }

    /**
     * Trait constants and property defaults are not analysed per using class, so they are checked once here.
     */
    public static function validateTraitMembers(SourceAnalyzer $source, Trait_ $trait): void
    {
        foreach ($trait->stmts as $member) {
            $items = match (true) {
                $member instanceof ClassConst => $member->consts,
                $member instanceof Property => $member->props,
                default => [],
            };

            foreach ($items as $item) {
                $value = $item instanceof Const_ ? $item->value : $item->default;

                if ($value !== null) {
                    self::validate($source, $value, $source->getSuppressedIssues());
                }
            }
        }
    }

    #[Override]
    public function enterNode(Node $node): ?int
    {
        if (!$node instanceof Closure && !$node instanceof ArrowFunction) {
            return null;
        }

        $message = match (true) {
            $node instanceof ArrowFunction => 'Constant expression contains invalid operations',
            $this->source->getCodebase()->analysis_php_version_id < 8_05_00
                => 'Closures in constant expressions require PHP 8.5',
            !$node->static => 'Closures in constant expressions must be static',
            $node->uses !== [] => 'Cannot use(...) variables in constant expression',
            default => null,
        };

        if ($message !== null) {
            IssueBuffer::maybeAdd(
                new ParseError($message, new CodeLocation($this->source, $node)),
                $this->suppressed_issues,
            );
        }

        // the closure body is ordinary code, not a constant expression
        return self::DONT_TRAVERSE_CHILDREN;
    }
}
