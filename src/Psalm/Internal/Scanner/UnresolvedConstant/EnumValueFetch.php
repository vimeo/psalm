<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

/**
 * @psalm-immutable
 * @internal
 */
class EnumValueFetch extends EnumPropertyFetch
{
    public function __construct(string $fqcln, string $case)
    {
        parent::__construct($fqcln, $case, 'value');
    }
}
