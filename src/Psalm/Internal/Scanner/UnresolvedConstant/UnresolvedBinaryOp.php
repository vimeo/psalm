<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
abstract class UnresolvedBinaryOp extends UnresolvedConstantComponent
{
    use ImmutableNonCloneableTrait;

    public function __construct(
        public readonly UnresolvedConstantComponent $left,
        public readonly UnresolvedConstantComponent $right,
    ) {
    }
}
