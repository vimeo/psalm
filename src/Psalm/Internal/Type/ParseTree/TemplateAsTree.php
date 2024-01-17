<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class TemplateAsTree extends ParseTree
{
    public function __construct(public string $param_name, public string $as, ?ParseTree $parent = null)
    {
        $this->parent = $parent;
    }
}
