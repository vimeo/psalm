<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @internal
 * @psalm-immutable
 */
abstract class UnresolvedBinaryOp extends UnresolvedConstantComponent
{
    use ImmutableNonCloneableTrait;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly UnresolvedConstantComponent $left,
        public readonly UnresolvedConstantComponent $right,
    ) {
    }
}
