<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class TemplateIsTree extends ParseTree
{
    /**
     * @param bool $negated whether the condition is `is not`
     * @psalm-mutation-free
     */
    public function __construct(
        public string $param_name,
        ?ParseTree $parent = null,
        public bool $negated = false,
    )
    {
        $this->parent = $parent;
    }
}
