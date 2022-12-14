<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 *
 * @internal
 */
class ArrayOffsetFetch extends UnresolvedConstantComponent
{
    /** @var UnresolvedConstantComponent */
    public UnresolvedConstantComponent $array;

    /** @var UnresolvedConstantComponent */
    public UnresolvedConstantComponent $offset;

    public function __construct(UnresolvedConstantComponent $left, UnresolvedConstantComponent $right)
    {
        $this->array = $left;
        $this->offset = $right;
    }
}
