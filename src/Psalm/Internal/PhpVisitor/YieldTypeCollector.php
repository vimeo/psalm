<?php

namespace Psalm\Internal\PhpVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

/**
 * @internal
 */
final class YieldTypeCollector extends NodeVisitorAbstract
{
    /** @var list<Union> */
    private array $yield_types = [];

    private NodeDataProvider $nodes;

    public function __construct(NodeDataProvider $nodes)
    {
        $this->nodes = $nodes;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Yield_) {
            $key_type = null;

            if ($node->key && $node_key_type = $this->nodes->getType($node->key)) {
                $key_type = $node_key_type;
            }

            if ($node->value
                && $value_type = $this->nodes->getType($node->value)
            ) {
                $generator_type = new TGenericObject(
                    'Generator',
                    [
                        $key_type ? $key_type : Type::getInt(),
                        $value_type,
                        Type::getMixed(),
                        Type::getMixed(),
                    ],
                );

                $this->yield_types []= new Union([$generator_type]);
                return null;
            }

            $this->yield_types []= Type::getMixed();
        } elseif ($node instanceof YieldFrom) {
            if ($node_expr_type = $this->nodes->getType($node->expr)) {
                $this->yield_types []= $node_expr_type;
                return null;
            }

            $this->yield_types []= Type::getMixed();
        } elseif ($node instanceof FunctionLike) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @return list<Union>
     */
    public function getYieldTypes(): array
    {
        return $this->yield_types;
    }
}
