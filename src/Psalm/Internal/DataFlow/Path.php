<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @internal
 * @psalm-immutable
 */
final class Path
{
    use ImmutableNonCloneableTrait;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $type,
        public readonly int $length,
        public readonly int $added_taints = 0,
        public readonly int $removed_taints = 0,
    ) {
    }
}
