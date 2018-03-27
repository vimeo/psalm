<?php
namespace Psalm\Type\ParseTree;

class CallableParamTree extends \Psalm\Type\ParseTree
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
