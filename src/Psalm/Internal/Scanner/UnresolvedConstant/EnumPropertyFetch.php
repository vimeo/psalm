<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
abstract class EnumPropertyFetch extends UnresolvedConstantComponent
{
    public function __construct(public string $fqcln, public string $case)
    {
    }
}
