<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class CallableParamTree extends ParseTree
{
    public bool $variadic = false;

    public bool $has_default = false;

    /**
     * Param name, without the $ prefix
     *
     * @var null|non-empty-string
     */
    public ?string $name = null;
}
