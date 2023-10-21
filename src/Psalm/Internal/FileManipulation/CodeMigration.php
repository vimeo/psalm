<?php

declare(strict_types=1);

namespace Psalm\Internal\FileManipulation;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class CodeMigration
{
    use ImmutableNonCloneableTrait;

    public function __construct(public string $source_file_path, public int $source_start, public int $source_end, public string $destination_file_path, public int $destination_start)
    {
    }
}
