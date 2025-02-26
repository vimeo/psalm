<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Psalm\Storage\ImmutableNonCloneableTrait;
use Psalm\Type\TaintKind;

/**
 * @psalm-immutable
 * @internal
 */
final class Path
{
    use ImmutableNonCloneableTrait;

    /**
     * @param int-mask-of<TaintKind::*> $unescaped_taints
     * @param int-mask-of<TaintKind::*> $escaped_taints
     */
    public function __construct(
        public readonly string $type,
        public readonly int $length,
        public readonly int $unescaped_taints = 0,
        public readonly int $escaped_taints = 0,
    ) {
    }
}
