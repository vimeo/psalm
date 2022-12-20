<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
class KeyValuePair extends UnresolvedConstantComponent
{
    public ?UnresolvedConstantComponent $key = null;

    public UnresolvedConstantComponent $value;

    public function __construct(?UnresolvedConstantComponent $key, UnresolvedConstantComponent $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
