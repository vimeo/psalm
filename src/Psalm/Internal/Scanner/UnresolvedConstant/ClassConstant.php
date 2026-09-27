<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @internal
 * @psalm-immutable
 */
final class ClassConstant extends UnresolvedConstantComponent
{
    /**
     * @param int $fqcln interned class name
     * @param int $name interned constant name
     * @psalm-mutation-free
     */
    public function __construct(public readonly int $fqcln, public readonly int $name)
    {
    }
}
