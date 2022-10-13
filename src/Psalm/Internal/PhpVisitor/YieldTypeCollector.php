<?php

namespace Psalm\Internal\PhpVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Expr\Yield_;
use PhpParser\NodeVisitorAbstract;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

/**
 * @internal
 */
class YieldTypeCollector extends NodeVisitorAbstract
{
    /** @var list<Union> */
    private array $yield_types = [];

    private NodeDataProvider $nodes;

    public function __construct(NodeDataProvider $nodes)
    {
        $this->nodes = $nodes;
    }

    public function enterNode(Node $stmt): ?Node
    {
        if ($stmt instanceof Yield_) {
            $key_type = null;

            if ($stmt->key && $stmt_key_type = $this->nodes->getType($stmt->key)) {
                $key_type = $stmt_key_type;
            }

            if ($stmt->value
                && $value_type = $this->nodes->getType($stmt->value)
            ) {
                $generator_type = new TGenericObject(
                    'Generator',
                    [
                        $key_type ? clone $key_type : Type::getInt(),
                        clone $value_type,
                        Type::getMixed(),
                        Type::getMixed()
                    ]
                );

                $this->yield_types []= new Union([$generator_type]);
                return null;
            }

            $this->yield_types []= Type::getMixed();
        } elseif ($stmt instanceof YieldFrom) {
            if ($stmt_expr_type = $this->nodes->getType($stmt->expr)) {
                $this->yield_types []= $stmt_expr_type;
                return null;
            }

            $this->yield_types []= Type::getMixed();
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
