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
        if (stripos($codebase->file_provider->getContents($file_storage->file_path), 'class_alias') !== false) {
            self::scanClassAliases($file_storage, $stmts);
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
