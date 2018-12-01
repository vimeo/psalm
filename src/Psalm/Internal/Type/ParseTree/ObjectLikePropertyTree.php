<?php
namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class ObjectLikePropertyTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var string
     */
    public $value;

    /**
     * @param string $value
     * @param \Psalm\Internal\Type\ParseTree|null $parent
     */
    public function __construct($value, \Psalm\Internal\Type\ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
    }
}
