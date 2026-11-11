<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Internal\DataFlow\DataFlowNode;

/**
 * Collects what a try body assigned to variables, also inside branches that leave the body by throwing.
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
     * The classes the catches of the try name, in lowercase
     *
     * @var list<string>
     */
    public array $catch_classes = [];

    /**
     * The try the body of this one is in
     */
    public ?TryScope $outer = null;
}
