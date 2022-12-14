<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
class ArraySpread extends UnresolvedConstantComponent
{
    public UnresolvedConstantComponent $array;

    public function __construct(UnresolvedConstantComponent $array)
    {
        $this->array = $array;
    }
}
