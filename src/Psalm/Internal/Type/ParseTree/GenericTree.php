<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class GenericTree extends ParseTree
{
    public string $value;

    public bool $terminated = false;

    public function __construct(string $value, ?ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
    }
}
