<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

/**
 * @psalm-immutable
 * @internal
 */
class EnumNameFetch extends EnumPropertyFetch
{
    public function __construct(string $fqcln, string $case)
    {
        parent::__construct($fqcln, $case, 'name');
    }
}
