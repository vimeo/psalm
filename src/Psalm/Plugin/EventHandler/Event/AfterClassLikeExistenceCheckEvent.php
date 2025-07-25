<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

final class AfterClassLikeExistenceCheckEvent
{
    /**
     * @param FileManipulation[] $file_replacements
     * @internal
     */
    public function __construct(
        private readonly string $fq_class_name,
        private readonly CodeLocation $code_location,
        private readonly StatementsSource $statements_source,
        private readonly Codebase $codebase,
        private array $file_replacements = [],
    ) {
    }

    public function getFqClassName(): string
    {
        return $this->fq_class_name;
    }

    public function getCodeLocation(): CodeLocation
    {
        return $this->code_location;
    }

    public function getStatementsSource(): StatementsSource
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
