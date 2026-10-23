<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeAbstract;
use Psalm\Codebase;
use Psalm\Internal\Type\TypeVariableTracker;
use Psalm\Internal\TypeVisitor\TypeVariableResolver;
use Psalm\NodeTypeProvider;
use Psalm\Type\Union;

/**
 * The view of a statements analyzer's node types handed out to code that is
 * not part of the analysis of the current function-like: plugins and return
 * type providers.
 *
 * Inside the function-like a class template with a mutation channel is carried
 * as a type variable (e.g. `` `_0 ``) so that later calls can still constrain
 * it; the analysis resolves such a variable itself at the read sites it
 * understands. A plugin, however, only knows arrays and objects: a variable it
 * meets — bare, or nested in the type parameters of the receiver it inspects —
 * would look like an unknown atomic. This provider therefore resolves every
 * variable, however deeply nested, to the shape inferred at its construction
 * site, while the analyzer's own bookkeeping keeps the live variables.
 *
 * @internal
 */
final class TypeVariableResolvingNodeTypeProvider implements NodeTypeProvider
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly NodeDataProvider $node_data,
        private readonly TypeVariableTracker $type_variable_tracker,
        private readonly Codebase $codebase,
    ) {
    }

    /**
     * @param Expr|Name|Return_ $node
     */
    #[Override]
    public function setType(NodeAbstract $node, Union $type): void
    {
        $this->node_data->setType($node, $type);
    }

    /**
     * @param Expr|Name|Return_ $node
     */
    #[Override]
    public function getType(NodeAbstract $node): ?Union
    {
        $type = $this->node_data->getType($node);

        // no variable has been minted in this function-like, so no type can
        // mention one and the traversal is skipped
        if ($type === null || !$this->type_variable_tracker->hasVariables()) {
            return $type;
        }

        $resolver = new TypeVariableResolver($this->codebase);
        $resolver->traverse($type);

        return $type;
    }
}
