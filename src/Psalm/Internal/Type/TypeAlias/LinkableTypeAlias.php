<?php

declare(strict_types=1);

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

    public function __construct(public string $declaring_fq_classlike_name, public string $alias_name, public int $line_number, public int $start_offset, public int $end_offset)
    {
    }
}
