<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;
use Psalm\Type\Variance;

/**
 * The `covariant` or `contravariant` prefix of a generic type argument; its only child is the argument.
 *
 * @internal
 */
final class VarianceTree extends ParseTree
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(public Variance $variance, ?ParseTree $parent = null)
    {
        $this->parent = $parent;
    }
}
