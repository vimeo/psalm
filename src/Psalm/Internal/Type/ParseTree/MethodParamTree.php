<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class MethodParamTree extends ParseTree
{
    public bool $variadic;

    public string $default = '';

    public bool $byref;

    public string $name;

    public function __construct(
        string $name,
        bool $byref,
        bool $variadic,
        ?ParseTree $parent = null
    ) {
        $this->name = $name;
        $this->byref = $byref;
        $this->variadic = $variadic;
        $this->parent = $parent;
    }
}
