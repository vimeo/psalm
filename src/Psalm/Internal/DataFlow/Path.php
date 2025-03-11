<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class Path
{
    use ImmutableNonCloneableTrait;

    public function __construct(
        public readonly string $type,
        public readonly int $length,
        public readonly int $added_taints = 0,
        public readonly int $removed_taints = 0,
    ) {
    }
}
