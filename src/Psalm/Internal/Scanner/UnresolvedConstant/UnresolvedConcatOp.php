<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
class UnresolvedConcatOp extends UnresolvedBinaryOp
{
    use ImmutableNonCloneableTrait;
}
