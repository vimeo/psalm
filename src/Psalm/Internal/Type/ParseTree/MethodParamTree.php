<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class MethodParamTree extends ParseTree
{
    public string $default = '';

    public function __construct(
        public string $name,
        public bool $byref,
        public bool $variadic,
        ?ParseTree $parent = null,
    ) {
        $this->parent = $parent;
    }
}
