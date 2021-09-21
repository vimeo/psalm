<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use Psalm\Type;

class ArrayCreationInfo
{
    /**
     * @var list<Type\Atomic>
     */
    public $item_key_atomic_types = [];

    /**
     * @var list<Type\Atomic>
     */
    public $item_value_atomic_types = [];

    /**
     * @var array<int|string, Type\Union>
     */
    public $property_types = [];

    /**
     * @var array<string, true>
     */
    public $class_strings = [];

    /**
     * @var bool
     */
    public $can_create_objectlike = true;

    /**
     * @var array<int|string, true>
     */
    public $array_keys = [];

    /**
     * @var int
     */
    public $int_offset = 0;

    /**
     * @var bool
     */
    public $all_list = true;

    /**
     * @var array<string, \Psalm\Internal\DataFlow\DataFlowNode>
     */
    public $parent_taint_nodes = [];
}
