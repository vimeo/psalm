<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @internal
 * @psalm-immutable
 */
abstract class EnumPropertyFetch extends UnresolvedConstantComponent
{
    /**
     * @param int $fqcln interned enum name
     * @param int $case interned case name
     * @psalm-mutation-free
     */
    public function __construct(public readonly int $fqcln, public readonly int $case)
    {
    }
}
