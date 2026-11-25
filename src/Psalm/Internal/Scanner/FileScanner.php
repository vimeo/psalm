<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner;

use Override;
use PhpParser;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\FileSource;
use Psalm\Internal\PhpVisitor\ReflectorVisitor;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Storage\FileStorage;

use function count;
use function is_string;
use function ltrim;
use function stripos;
use function strtolower;

/**
 * @internal
 * @psalm-consistent-constructor
 */
class FileScanner implements FileSource
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(public string $file_path, public string $file_name, public bool $will_analyze)
    {
    }

    public function scan(
        Codebase $codebase,
        FileStorage $file_storage,
        bool $storage_from_cache = false,
        ?Progress $progress = null,
    ): void {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        if ((!$this->will_analyze || $file_storage->deep_scan)
            && $storage_from_cache
            && !$codebase->register_stub_files
        ) {
            return;
        }

        $stmts = $codebase->getStatementsForFile(
            $file_storage->file_path,
            $progress,
        );

        foreach ($stmts as $stmt) {
            if (!$stmt instanceof PhpParser\Node\Stmt\ClassLike
                && !$stmt instanceof PhpParser\Node\Stmt\Function_
                && !($stmt instanceof PhpParser\Node\Stmt\Expression
                    && $stmt->expr instanceof PhpParser\Node\Expr\Include_)
            ) {
                $file_storage->has_extra_statements = true;
                break;
            }
        }

        if ($this->will_analyze) {
            $progress->debug('Deep scanning ' . $file_storage->file_path . "\n");
        } else {
            $progress->debug('Scanning ' . $file_storage->file_path . "\n");
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new ReflectorVisitor($codebase, $this, $file_storage),
        );

        $traverser->traverse($stmts);

        // the scan skips the bodies of the function-likes of the files it doesn't analyze, and the code of the
        // conditions it decides are false
        $contents = $codebase->file_provider->getContents($file_storage->file_path);
        if (stripos($contents, 'class_alias') !== false) {
            self::scanClassAliases($file_storage, $stmts);
        }

        if (stripos($contents, 'const') !== false) {
            self::scanConstStatements($file_storage, $stmts);
        }

        if (stripos($contents, 'define') !== false) {
            self::scanDefines($file_storage, $stmts);
        }

        if (stripos($contents, 'global') !== false) {
            self::scanGlobalVariables($file_storage, $stmts);
        }

        $file_storage->deep_scan = $this->will_analyze;
    }

    /**
     * Records the names the class_alias() calls of the file may give a class (see ConstantFlags)
     *
     * @param list<PhpParser\Node\Stmt> $stmts
     */
    private static function scanClassAliases(FileStorage $file_storage, array $stmts): void
    {
        $nodes = (new NodeFinder())->find(
            $stmts,
            static fn(PhpParser\Node $node): bool => ($node instanceof PhpParser\Node\Scalar\String_
                    && strtolower(ltrim($node->value, '\\')) === 'class_alias')
                || ($node instanceof PhpParser\Node\Expr\FuncCall
                    && $node->name instanceof PhpParser\Node\Name
                    && (strtolower($node->name->getLast()) === 'class_alias'
                        || strtolower((string) $node->name->getAttribute('resolvedName')) === 'class_alias')),
        );

        foreach ($nodes as $node) {
            // a callable string, or a first-class callable: called with any name
            if (!$node instanceof PhpParser\Node\Expr\FuncCall || $node->isFirstClassCallable()) {
                $file_storage->has_dynamic_class_alias = true;

                continue;
            }

            $alias = null;

            foreach ($node->getArgs() as $offset => $arg) {
                if ($arg->unpack) {
                    $file_storage->has_dynamic_class_alias = true;

                    continue 2;
                }

                if ($arg->name === null ? $offset === 1 : strtolower($arg->name->name) === 'alias') {
                    $alias = $arg->value;
                }
            }

            if ($alias === null) {
                continue;
            }

            if ($alias instanceof PhpParser\Node\Scalar\String_) {
                $alias_name = $alias->value;
            } elseif ($alias instanceof PhpParser\Node\Expr\ClassConstFetch
                && $alias->class instanceof PhpParser\Node\Name
                && $alias->name instanceof PhpParser\Node\Identifier
                && strtolower($alias->name->name) === 'class'
            ) {
                $alias_name = (string) $alias->class->getAttribute('resolvedName');
            } else {
                $alias_name = '';
            }

            if ($alias_name !== '') {
                $file_storage->possible_class_aliases[strtolower(ltrim($alias_name, '\\'))] = true;
            } else {
                $file_storage->has_dynamic_class_alias = true;
            }
        }
    }

    /**
     * Records the global constants the `const` statements of the file declare (see ConstantFlags)
     *
     * @param list<PhpParser\Node\Stmt> $stmts
     */
    private static function scanConstStatements(FileStorage $file_storage, array $stmts): void
    {
        foreach ($stmts as $stmt) {
            $namespace = $stmt instanceof PhpParser\Node\Stmt\Namespace_ && $stmt->name !== null
                ? $stmt->name->toString() . '\\'
                : '';

            foreach ($stmt instanceof PhpParser\Node\Stmt\Namespace_ ? $stmt->stmts : [$stmt] as $namespace_stmt) {
                foreach ($namespace_stmt instanceof PhpParser\Node\Stmt\Declare_
                    ? $namespace_stmt->stmts ?? []
                    : [$namespace_stmt] as $declaration
                ) {
                    if ($declaration instanceof PhpParser\Node\Stmt\Const_) {
                        foreach ($declaration->consts as $const) {
                            self::addDeclaredConstant($file_storage, $namespace . $const->name->name);
                        }
                    }
                }
            }
        }
    }

    /**
     * Records the global constants the define() calls of the file may declare (see ConstantFlags)
     *
     * @param list<PhpParser\Node\Stmt> $stmts
     */
    private static function scanDefines(FileStorage $file_storage, array $stmts): void
    {
        $nodes = (new NodeFinder())->find(
            $stmts,
            static fn(PhpParser\Node $node): bool => ($node instanceof PhpParser\Node\Scalar\String_
                    && strtolower(ltrim($node->value, '\\')) === 'define')
                || ($node instanceof PhpParser\Node\Expr\FuncCall
                    && $node->name instanceof PhpParser\Node\Name
                    && (strtolower($node->name->getLast()) === 'define'
                        || strtolower((string) $node->name->getAttribute('resolvedName')) === 'define')),
        );

        foreach ($nodes as $node) {
            // a callable string, or a first-class callable: called with any name
            if (!$node instanceof PhpParser\Node\Expr\FuncCall || $node->isFirstClassCallable()) {
                $file_storage->has_dynamic_define = true;

                continue;
            }

            $constant_name = null;

            foreach ($node->getArgs() as $offset => $arg) {
                if ($arg->unpack) {
                    $file_storage->has_dynamic_define = true;

                    continue 2;
                }

                if ($arg->name === null ? $offset === 0 : strtolower($arg->name->name) === 'constant_name') {
                    $constant_name = $arg->value;
                }
            }

            if ($constant_name instanceof PhpParser\Node\Scalar\String_) {
                self::addDeclaredConstant($file_storage, ltrim($constant_name->value, '\\'));
            } elseif ($constant_name !== null) {
                $file_storage->has_dynamic_define = true;
            }
        }
    }

    /**
     * Records the variables of the global scope the code of the file may write from elsewhere: those it declares
     * `global` and those it reads in `$GLOBALS` (see ConstantFlags)
     *
     * @param list<PhpParser\Node\Stmt> $stmts
     */
    private static function scanGlobalVariables(FileStorage $file_storage, array $stmts): void
    {
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($stmts, PhpParser\Node\Stmt\Global_::class) as $global) {
            foreach ($global->vars as $var) {
                if ($var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
                    $file_storage->global_variables[$var->name] = true;
                } else {
                    $file_storage->has_dynamic_global = true;
                }
            }
        }

        $globals = $finder->find(
            $stmts,
            static fn(PhpParser\Node $node): bool => $node instanceof PhpParser\Node\Expr\Variable
                && $node->name === 'GLOBALS',
        );

        $fetches = $finder->find(
            $stmts,
            static fn(PhpParser\Node $node): bool => $node instanceof PhpParser\Node\Expr\ArrayDimFetch
                && $node->var instanceof PhpParser\Node\Expr\Variable
                && $node->var->name === 'GLOBALS',
        );

        // `$GLOBALS` other than one of its items: `foreach ($GLOBALS as &$value)`, `extract($GLOBALS)`...
        if (count($fetches) < count($globals)) {
            $file_storage->has_dynamic_global = true;
        }

        foreach ($fetches as $fetch) {
            $dim = $fetch instanceof PhpParser\Node\Expr\ArrayDimFetch ? $fetch->dim : null;

            if ($dim instanceof PhpParser\Node\Scalar\String_) {
                $file_storage->global_variables[$dim->value] = true;
            } else {
                $file_storage->has_dynamic_global = true;
            }
        }
    }

    private static function addDeclaredConstant(FileStorage $file_storage, string $name): void
    {
        // the namespace of a constant is case-insensitive
        $name = strtolower($name);
        $file_storage->declared_constants[$name] = ($file_storage->declared_constants[$name] ?? 0) + 1;
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getFilePath(): string
    {
        return $this->file_path;
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getFileName(): string
    {
        return $this->file_name;
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getRootFilePath(): string
    {
        return $this->file_path;
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getRootFileName(): string
    {
        return $this->file_name;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getAliases(): Aliases
    {
        return new Aliases();
    }
}
