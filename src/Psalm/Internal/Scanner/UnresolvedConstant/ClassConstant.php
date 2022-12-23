<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
class ClassConstant extends UnresolvedConstantComponent
{
    public string $fqcln;

    public string $name;

    public function __construct(string $fqcln, string $name)
    {
        $this->fqcln = $fqcln;
        $this->name = $name;
    }
}
