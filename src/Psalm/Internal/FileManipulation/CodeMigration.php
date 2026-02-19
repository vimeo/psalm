<?php

declare(strict_types=1);

namespace Psalm\Internal\FileManipulation;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @internal
 * @psalm-immutable
 */
final class CodeMigration
{
    use ImmutableNonCloneableTrait;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $source_file_path,
        public readonly int $source_start,
        public readonly int $source_end,
        public readonly string $destination_file_path,
        public readonly int $destination_start,
    ) {
    }
}
