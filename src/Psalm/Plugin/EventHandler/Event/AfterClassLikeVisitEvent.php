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
        public readonly ClassLike $stmt,
        public readonly ClassLikeStorage $storage,
        public readonly FileSource $statements_source,
        public readonly Codebase $codebase,
        public array $file_replacements = [],
    ) {
    }
}
