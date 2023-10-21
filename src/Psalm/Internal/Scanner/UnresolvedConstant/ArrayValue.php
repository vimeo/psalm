<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
final class ArrayValue extends UnresolvedConstantComponent
{
    /** @param list<KeyValuePair|ArraySpread> $entries */
    public function __construct(public readonly array $entries)
    {
    }
}
