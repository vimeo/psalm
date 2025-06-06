<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner;

use Override;
use PhpParser;
use PhpParser\NodeTraverser;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\FileSource;
use Psalm\Internal\PhpVisitor\ReflectorVisitor;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Storage\FileStorage;

/**
 * @internal
 * @psalm-consistent-constructor
 */
class FileScanner implements FileSource
{
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

        $file_storage->deep_scan = $this->will_analyze;
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

    /** @psalm-mutation-free */
    #[Override]
    public function getAliases(): Aliases
    {
        return new Aliases();
    }
}
