<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class UnresolvedMultiplicationOp extends UnresolvedBinaryOp
{
    use ImmutableNonCloneableTrait;
}
