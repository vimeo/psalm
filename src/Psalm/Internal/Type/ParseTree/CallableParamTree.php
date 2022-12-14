<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class CallableParamTree extends ParseTree
{
    public bool $variadic = false;

    public bool $has_default = false;
}
