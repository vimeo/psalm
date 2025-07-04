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
        public readonly string $fq_class_name,
        public readonly CodeLocation $code_location,
        public readonly StatementsSource $statements_source,
        public readonly Codebase $codebase,
        public array $file_replacements = [],
    ) {
    }
}
