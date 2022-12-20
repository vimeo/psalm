<?php

namespace Psalm\Internal\Provider;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeAbstract;
use Psalm\NodeTypeProvider;
use Psalm\Storage\Assertion;
use Psalm\Storage\Possibilities;
use Psalm\Type\Union;
use SplObjectStorage;

/**
 * @internal
 */
class NodeDataProvider implements NodeTypeProvider
{
    /** @var SplObjectStorage<Node, Union> */
    private SplObjectStorage $node_types;

    /**
     * @var SplObjectStorage<Node,list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>|null>
     */
    private SplObjectStorage $node_assertions;

    /** @var SplObjectStorage<Node, array<int, Possibilities>> */
    private SplObjectStorage $node_if_true_assertions;

    /** @var SplObjectStorage<Node, array<int, Possibilities>> */
    private SplObjectStorage $node_if_false_assertions;

    public bool $cache_assertions = true;

    public function __construct()
    {
        $this->node_types = new SplObjectStorage();
        $this->node_assertions = new SplObjectStorage();
        $this->node_if_true_assertions = new SplObjectStorage();
        $this->node_if_false_assertions = new SplObjectStorage();
    }

    /**
     * @param Expr|Name|Return_ $node
     */
    public function setType(NodeAbstract $node, Union $type): void
    {
        $this->node_types[$node] = $type;
    }

    /**
     * @param Expr|Name|Return_ $node
     */
    public function getType(NodeAbstract $node): ?Union
    {
        return $this->node_types[$node] ?? null;
    }

    /**
     * @param list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>|null $assertions
     */
    public function setAssertions(Expr $node, ?array $assertions): void
    {
        if (!$this->cache_assertions) {
            return;
        }

        $this->node_assertions[$node] = $assertions;
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>|null
     */
    public function getAssertions(Expr $node): ?array
    {
        if (!$this->cache_assertions) {
            return null;
        }

        return $this->node_assertions[$node] ?? null;
    }

    /**
     * @param FuncCall|MethodCall|StaticCall|New_ $node
     * @param array<int, Possibilities>  $assertions
     */
    public function setIfTrueAssertions(Expr $node, array $assertions): void
    {
        $this->node_if_true_assertions[$node] = $assertions;
    }

    /**
     * @param Expr\FuncCall|MethodCall|StaticCall|New_ $node
     * @return array<int, Possibilities>|null
     */
    public function getIfTrueAssertions(Expr $node): ?array
    {
        return $this->node_if_true_assertions[$node] ?? null;
    }

    /**
     * @param FuncCall|MethodCall|StaticCall|New_ $node
     * @param array<int, Possibilities>  $assertions
     */
    public function setIfFalseAssertions(Expr $node, array $assertions): void
    {
        $this->node_if_false_assertions[$node] = $assertions;
    }

    /**
     * @param FuncCall|MethodCall|StaticCall|New_ $node
     * @return array<int, Possibilities>|null
     */
    public function getIfFalseAssertions(Expr $node): ?array
    {
        return $this->node_if_false_assertions[$node] ?? null;
    }

    public function isPureCompatible(Expr $node): bool
    {
        $node_type = $this->getType($node);

        return ($node_type && $node_type->reference_free) || $node->getAttribute('pure', false);
    }

    public function clearNodeOfTypeAndAssertions(Expr $node): void
    {
        unset($this->node_types[$node], $this->node_assertions[$node]);
    }
}
