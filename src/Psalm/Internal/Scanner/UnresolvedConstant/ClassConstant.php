<?php

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 *
 * @internal
 */
class ClassConstant extends UnresolvedConstantComponent
{
    /** @var string */
    public string $fqcln;

    /** @var string */
    public string $name;

    public function __construct(string $fqcln, string $name)
    {
        $this->fqcln = $fqcln;
        $this->name = $name;
    }
}
