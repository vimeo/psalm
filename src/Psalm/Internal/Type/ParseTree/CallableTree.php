<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
final class CallableTree extends ParseTree
{
    public bool $terminated = false;

    /**
     * The purity given as `Closure<...>(...)`/`callable<...>(...)`: a capability set or a
     * purity template, if any.
     */
    public ?ParseTree $purity = null;

    /**
     * @psalm-mutation-free
     */
    public function __construct(public string $value, ?ParseTree $parent = null)
    {
        $this->parent = $parent;
    }
}
