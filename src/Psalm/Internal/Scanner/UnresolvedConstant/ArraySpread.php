<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 *
 * @internal
 */
class ArraySpread extends UnresolvedConstantComponent
{
    /** @var UnresolvedConstantComponent */
    public $array;

    public function __construct(UnresolvedConstantComponent $array)
    {
        $this->array = $array;
    }
}
