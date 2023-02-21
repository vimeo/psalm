<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
class ArrayOffsetFetch extends UnresolvedConstantComponent
{
    public UnresolvedConstantComponent $array;

    public UnresolvedConstantComponent $offset;

    public function __construct(UnresolvedConstantComponent $left, UnresolvedConstantComponent $right)
    {
        $this->array = $left;
        $this->offset = $right;
    }
}
