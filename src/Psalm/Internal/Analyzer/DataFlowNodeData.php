<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class DataFlowNodeData
{
    use ImmutableNonCloneableTrait;

    public function __construct(
        public readonly string $label,
        public readonly int $line_from,
        public readonly int $line_to,
        public readonly string $file_name,
        public readonly string $file_path,
        public readonly string $snippet,
        public readonly int $from,
        public readonly int $to,
        public readonly int $snippet_from,
        public readonly int $column_from,
        public readonly int $column_to,
    ) {
    }
}
