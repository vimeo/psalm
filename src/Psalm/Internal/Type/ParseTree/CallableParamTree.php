<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class CallableParamTree extends ParseTree
{
    /**
     * @var bool
     */
    public bool $variadic = false;

    /**
     * @var bool
     */
    public bool $has_default = false;
}
