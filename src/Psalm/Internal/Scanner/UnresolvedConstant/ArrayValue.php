<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
class ArrayValue extends UnresolvedConstantComponent
{
    /** @var array<int, KeyValuePair|ArraySpread> */
    public array $entries;

    /** @param list<KeyValuePair|ArraySpread> $entries */
    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }
}
