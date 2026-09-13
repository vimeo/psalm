<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\TypeAlias;

use Psalm\Internal\Type\TypeAlias;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @internal
 * @psalm-immutable
 */
final class LinkableTypeAlias implements TypeAlias
{
    use ImmutableNonCloneableTrait;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $declaring_fq_classlike_name,
        public readonly string $alias_name,
        public readonly int $line_number,
        public readonly int $start_offset,
        public readonly int $end_offset,
    ) {
    }
}
