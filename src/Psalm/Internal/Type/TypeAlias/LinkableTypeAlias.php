<?php

namespace Psalm\Internal\Type\TypeAlias;

use Psalm\Internal\Type\TypeAlias;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class LinkableTypeAlias implements TypeAlias
{
    use ImmutableNonCloneableTrait;

    public string $declaring_fq_classlike_name;

    public string $alias_name;

    public int $line_number;

    public int $start_offset;

    public int $end_offset;

    public function __construct(
        string $declaring_fq_classlike_name,
        string $alias_name,
        int $line_number,
        int $start_offset,
        int $end_offset
    ) {
        $this->declaring_fq_classlike_name = $declaring_fq_classlike_name;
        $this->alias_name = $alias_name;
        $this->line_number = $line_number;
        $this->start_offset = $start_offset;
        $this->end_offset = $end_offset;
    }
}
