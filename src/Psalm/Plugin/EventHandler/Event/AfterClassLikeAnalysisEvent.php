<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node;
use Psalm\Codebase;
use Psalm\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;

final class AfterClassLikeAnalysisEvent
{
    /**
     * Called after a statement has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     * @internal
     */
    public function __construct(
        public readonly Node\Stmt\ClassLike $stmt,
        public readonly ClassLikeStorage $classlike_storage,
        public readonly StatementsSource $statements_source,
        public readonly Codebase $codebase,
        public array $file_replacements = [],
    ) {
    }
}
