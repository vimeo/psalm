<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class TemplateIsTree extends ParseTree
{
    public string $param_name;

    public function __construct(string $param_name, ?ParseTree $parent = null)
    {
        $this->param_name = $param_name;
        $this->parent = $parent;
    }
}
