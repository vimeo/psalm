<?php
namespace Psalm\Type\ParseTree;

class MethodParamTree extends \Psalm\Type\ParseTree
{
    /**
     * @var bool
     */
    public $variadic;

    /**
     * @var string
     */
    public $default = '';

    /**
     * @var bool
     */
    public $byref;

    /**
     * @var string
     */
    public $name;

    /**
     * @param string $name
     * @param bool $byref
     * @param bool $variadic
     *
     * @param \Psalm\Type\ParseTree|null $parent
     */
    public function __construct($name, $byref, $variadic, \Psalm\Type\ParseTree $parent = null)
    {
        $this->name = $name;
        $this->byref = $byref;
        $this->variadic = $variadic;
        $this->parent = $parent;
    }
}
