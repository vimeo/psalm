<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Psalm\Storage\ImmutableNonCloneableTrait;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @internal
 * @psalm-immutable
 */
final class Path
{
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $type,
        public readonly int $length,
        public readonly int $added_taints = 0,
        public readonly int $removed_taints = 0,
        /** the taints a flow gets back of the shadows it has of them (see TaintShadows) */
        public readonly int $restored_taints = 0,
    ) {
    }
}
