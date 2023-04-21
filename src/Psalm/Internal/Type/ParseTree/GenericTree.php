<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class GenericTree extends ParseTree
{
    public string $value;

    public bool $terminated = false;

    public bool $is_unsealed_array_shape;

    public function __construct(string $value, ?ParseTree $parent = null, bool $is_unsealed_array_shape = false)
    {
        $this->value = $value;
        $this->parent = $parent;
        $this->is_unsealed_array_shape = $is_unsealed_array_shape;
    }
}
