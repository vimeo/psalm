<?php
namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class MethodParamTree extends \Psalm\Internal\Type\ParseTree
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
     * @param \Psalm\Internal\Type\ParseTree|null $parent
     */
    public function __construct($name, $byref, $variadic, \Psalm\Internal\Type\ParseTree $parent = null)
    {
        $this->name = $name;
        $this->byref = $byref;
        $this->variadic = $variadic;
        $this->parent = $parent;
    }
}
