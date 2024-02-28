<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
final class KeyValuePair extends UnresolvedConstantComponent
{
    public ?UnresolvedConstantComponent $key = null;

    public UnresolvedConstantComponent $value;

    public function __construct(?UnresolvedConstantComponent $key, UnresolvedConstantComponent $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
