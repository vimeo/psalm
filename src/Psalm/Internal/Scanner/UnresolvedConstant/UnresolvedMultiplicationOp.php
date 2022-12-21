<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
class UnresolvedMultiplicationOp extends UnresolvedBinaryOp
{
    use ImmutableNonCloneableTrait;
}
