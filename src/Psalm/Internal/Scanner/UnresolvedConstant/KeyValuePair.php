<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 *
 * @internal
 */
class KeyValuePair extends UnresolvedConstantComponent
{
    /** @var ?UnresolvedConstantComponent */
    public $key;

    /** @var UnresolvedConstantComponent */
    public $value;

    public function __construct(?UnresolvedConstantComponent $key, UnresolvedConstantComponent $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
