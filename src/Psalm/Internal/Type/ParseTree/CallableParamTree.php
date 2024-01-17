<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class CallableParamTree extends ParseTree
{
    public bool $variadic = false;

    public bool $has_default = false;
}
