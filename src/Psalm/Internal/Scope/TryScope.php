<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Internal\DataFlow\DataFlowNode;

/**
 * Collects what a try body assigned to variables, also inside branches that leave the body by throwing: what a
 * catch can see, and what is seen after the try.
 *
 * @internal
 */
final class TryScope
{
    /**
     * @var array<string, array<string, DataFlowNode>>
     */
    public array $parent_nodes = [];

    /**
     * What the throwing branches assigned that is seen after the try: only through a catch that goes on
     *
     * @var array<string, array<string, DataFlowNode>>
     */
    public array $post_try_parent_nodes = [];

    /**
     * The classes the catches of the try name, in lowercase
     *
     * @var list<string>
     */
    public array $catch_classes = [];

    /**
     * The classes of the catches that can continue after the try (the others always return or throw)
     *
     * @var list<string>
     */
    public array $continuing_catch_classes = [];

    /**
     * The try the body of this one is in
     */
    public ?TryScope $outer = null;
}
