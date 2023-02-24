<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
abstract class EnumPropertyFetch extends UnresolvedConstantComponent
{
    public string $fqcln;

    public string $case;

    public function __construct(string $fqcln, string $case)
    {
        $this->fqcln = $fqcln;
        $this->case = $case;
    }
}
