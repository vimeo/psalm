<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class KeyedArrayTree extends ParseTree
{
    /**
     * @var string
     */
    public string $value;

    /**
     * @var bool
     */
    public bool $terminated = false;

    public function __construct(string $value, ?ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
    }
}
