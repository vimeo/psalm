<?php
namespace Psalm\Internal\Type\ParseTree;

class CallableParamTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var bool
     */
    public $variadic = false;

    /**
     * @var bool
     */
    public $has_default = false;
}
