<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
class UnresolvedSubtractionOp extends UnresolvedBinaryOp
{
    use ImmutableNonCloneableTrait;
}
