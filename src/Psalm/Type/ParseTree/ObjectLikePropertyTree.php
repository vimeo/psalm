<?php
namespace Psalm\Type\ParseTree;

class ObjectLikePropertyTree extends \Psalm\Type\ParseTree
{
    /**
     * @var string
     */
    public $value;

    /**
     * @param string $value
     * @param \Psalm\Type\ParseTree|null $parent
     */
    public function __construct($value, \Psalm\Type\ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
    }
}
