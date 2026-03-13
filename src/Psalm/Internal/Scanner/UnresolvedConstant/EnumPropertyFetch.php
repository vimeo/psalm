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
     * @psalm-mutation-free
     */
    public function __construct(public readonly string $fqcln, public readonly string $case)
    {
    }
}
