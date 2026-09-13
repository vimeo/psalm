<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @internal
 * @psalm-immutable
 */
final class ArrayValue extends UnresolvedConstantComponent
{
    /**
     * @param list<KeyValuePair|ArraySpread> $entries
     * @psalm-mutation-free
     */
    public function __construct(public readonly array $entries)
    {
    }
}
