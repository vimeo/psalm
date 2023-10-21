<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class TemplateIsTree extends ParseTree
{
    public function __construct(public string $param_name, ?ParseTree $parent = null)
    {
        $this->parent = $parent;
    }
}
