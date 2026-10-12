<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * @internal
 */
final class ArrayCreationInfo
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

    /**
     * Holds the integer offset of the *last* element added
     *
     * -1 may mean no elements have been added yet, but can also mean there's an element with offset -1
     */
    public int $int_offset = -1;

    /**
     * Whether $int_offset is exact: once an array of unknown length with integer keys is unpacked,
     * it is only a lower bound, and the items added after it get no known key
     */
    public bool $int_offset_known = true;

    public bool $all_list = true;

    /**
     * @var array<string, DataFlowNode>
     */
    public array $parent_taint_nodes = [];

    /**
     * The nodes of the unpacked items, whose paths are added once the keys they end up at are
     * known, with the parent nodes of the arrays unpacked
     *
     * @var list<array{DataFlowNode, array<string, DataFlowNode>}>
     */
    public array $unpacked_nodes = [];

    public bool $can_be_empty = true;
}
