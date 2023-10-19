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

    public function __construct(public string $label, public int $line_from, public int $line_to, public string $file_name, public string $file_path, public string $snippet, public int $from, public int $to, public int $snippet_from, public int $column_from, public int $column_to)
    {
    }
}
