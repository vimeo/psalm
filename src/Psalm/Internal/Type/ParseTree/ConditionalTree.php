<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class ConditionalTree extends ParseTree
{
    public TemplateIsTree $condition;

    public function __construct(TemplateIsTree $condition, ?ParseTree $parent = null)
    {
        $this->condition = $condition;
        $this->parent = $parent;
    }
}
