<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * @internal
 */
class ArrayCreationInfo
{
    /**
     * @var list<Atomic>
     */
    public array $item_key_atomic_types = [];

    /**
     * @var list<Atomic>
     */
    public array $item_value_atomic_types = [];

    /**
     * @var array<int|string, Union>
     */
    public array $property_types = [];

    /**
     * @var array<string, true>
     */
    public array $class_strings = [];

    public bool $can_create_objectlike = true;

    /**
     * @var array<int|string, true>
     */
    public array $array_keys = [];

    public int $int_offset = 0;

    public bool $all_list = true;

    /**
     * @var array<string, DataFlowNode>
     */
    public array $parent_taint_nodes = [];

    public bool $can_be_empty = true;
}
