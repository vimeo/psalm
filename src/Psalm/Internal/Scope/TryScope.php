<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\Union;

/**
 * Collects what a try body assigned to variables, also inside branches that leave the body by throwing: what a
 * catch can see, and what is seen after the try.
 *
 * @internal
 */
final class TryScope
{
    /**
     * What each throw in the body assigned, with the type of the exception: a catch sees only the throws it
     * can take
     *
     * @var list<array{type: ?Union, nodes: array<string, array<string, DataFlowNode>>}>
     */
    public array $throws = [];

    /**
     * What the throwing branches assigned that is seen after the try: only through a catch that goes on
     *
     * @var array<string, array<string, DataFlowNode>>
     */
    public array $post_try_parent_nodes = [];

    /**
     * The nodes of the values the variables had before an assignment in the body: an exception can be thrown between
     * any two statements, so a catch and the finally still see them, but the code of the body after the assignment
     * does not
     *
     * @var array<string, array<string, DataFlowNode>>
     */
    public array $overwritten_parent_nodes = [];

    /**
     * The classes the catches of the try name, in lowercase
     *
     * @var list<string>
     */
    public array $catch_classes = [];

    /**
     * The classes each catch names, in lowercase
     *
     * @var array<int, list<string>>
     */
    public array $catch_classes_by_catch = [];

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
