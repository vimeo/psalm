<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class ConditionalTree extends ParseTree
{
    public TemplateIsTree $condition;

    public function __construct(TemplateIsTree $condition, ?ParseTree $parent = null)
    {
        $this->condition = $condition;
        $this->parent = $parent;
    }
}
