<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class MethodParamTree extends ParseTree
{
    /**
     * @var bool
     */
    public bool $variadic;

    /**
     * @var string
     */
    public string $default = '';

    /**
     * @var bool
     */
    public bool $byref;

    /**
     * @var string
     */
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
