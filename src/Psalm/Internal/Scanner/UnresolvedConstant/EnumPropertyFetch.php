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

    /** @var 'name'|'value' */
    public string $name;

    /**
     * @param string $fqcln
     * @param string $case
     * @param 'name'|'value' $name
     */
    public function __construct(string $fqcln, string $case, string $name)
    {
        $this->fqcln = $fqcln;
        $this->case = $case;
        $this->name = $name;
    }


}
