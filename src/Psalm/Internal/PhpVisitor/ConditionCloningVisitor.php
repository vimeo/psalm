<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeVisitorAbstract;
use Psalm\Internal\Provider\NodeDataProvider;

/**
 * @internal
 */
final class ConditionCloningVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly NodeDataProvider $type_provider,
    ) {
    }

    /**
     * @return Node\Expr
     */
    public function enterNode(Node $node): Node
    {
        /** @var Expr $node */
        $origNode = $node;

        $node = clone $node;

        $node_type = $this->type_provider->getType($origNode);

        if ($node_type) {
            $this->type_provider->setType($node, $node_type);
        }

        return $node;
    }
}
