<?php
namespace Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class TypeConstraintTree extends \Psalm\Internal\Type\ParseTree
{
    /**
     * @var string|null
     */
    public $name;

    /**
     * @var \Psalm\Internal\Type\ParseTree|null
     */
    public $value_tree;
}
