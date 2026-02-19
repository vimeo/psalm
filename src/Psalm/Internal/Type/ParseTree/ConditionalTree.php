<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class ConditionalTree extends ParseTree
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(public TemplateIsTree $condition, ?ParseTree $parent = null)
    {
        $this->parent = $parent;
    }
}
