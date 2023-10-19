<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Stmt\ClassLike;
use Psalm\Codebase;
use Psalm\FileManipulation;
use Psalm\FileSource;
use Psalm\Storage\ClassLikeStorage;

final class AfterClassLikeVisitEvent
{
    /**
     * @param  FileManipulation[] $file_replacements
     * @internal
     */
    public function __construct(
        private readonly ClassLike $stmt,
        private readonly ClassLikeStorage $storage,
        private readonly FileSource $statements_source,
        private readonly Codebase $codebase,
        private array $file_replacements = [],
    ) {
    }

    public function getStmt(): ClassLike
    {
        return $this->stmt;
    }

    public function getStorage(): ClassLikeStorage
    {
        return $this->storage;
    }

    public function getStatementsSource(): FileSource
    {
        return $this->statements_source;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /**
     * @return FileManipulation[]
     */
    public function getFileReplacements(): array
    {
        return $this->file_replacements;
    }

    /**
     * @param FileManipulation[] $file_replacements
     */
    public function setFileReplacements(array $file_replacements): void
    {
        $this->file_replacements = $file_replacements;
    }
}
