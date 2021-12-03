<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use PhpParser\Node;
use Psalm\NodeTypeProvider;
use Psalm\Type\Union;
use SplObjectStorage;

class NodeDataProvider implements NodeTypeProvider
{
    /** @var SplObjectStorage<Node, Union> */
    private $node_types;

    /**
     * @var SplObjectStorage<Node,list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>|null>
     */
    private $node_assertions;

    /** @var SplObjectStorage<Node, array<int, \Psalm\Storage\Assertion>> */
    private $node_if_true_assertions;

    /** @var SplObjectStorage<Node, array<int, \Psalm\Storage\Assertion>> */
    private $node_if_false_assertions;

    /** @var bool */
    public $cache_assertions = true;

    public function __construct()
    {
        $this->node_types = new SplObjectStorage();
        $this->node_assertions = new SplObjectStorage();
        $this->node_if_true_assertions = new SplObjectStorage();
        $this->node_if_false_assertions = new SplObjectStorage();
    }

    /**
     * @param Node\Expr|Node\Name|Node\Stmt\Return_ $node
     */
    public function setType(PhpParser\NodeAbstract $node, Union $type) : void
    {
        $this->node_types[$node] = $type;
    }

    /**
     * @param Node\Expr|Node\Name|Node\Stmt\Return_ $node
     */
    public function getType(PhpParser\NodeAbstract $node) : ?Union
    {
        return $this->node_types[$node] ?? null;
    }

    /**
     * @param list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>|null $assertions
     */
    public function setAssertions(Node\Expr $node, ?array $assertions) : void
    {
        if (!$this->cache_assertions) {
            return;
        }

        $this->node_assertions[$node] = $assertions;
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>|null
     */
    public function getAssertions(Node\Expr $node) : ?array
    {
        if (!$this->cache_assertions) {
            return null;
        }

        return $this->node_assertions[$node] ?? null;
    }

    /**
     * @param Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $node
     * @param array<int, \Psalm\Storage\Assertion>  $assertions
     */
    public function setIfTrueAssertions(Node\Expr $node, array $assertions) : void
    {
        $this->node_if_true_assertions[$node] = $assertions;
    }

    /**
     * @param Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $node
     * @return array<int, \Psalm\Storage\Assertion>|null
     */
    public function getIfTrueAssertions(Node\Expr $node) : ?array
    {
        return $this->node_if_true_assertions[$node] ?? null;
    }

    /**
     * @param Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $node
     * @param array<int, \Psalm\Storage\Assertion>  $assertions
     */
    public function setIfFalseAssertions(Node\Expr $node, array $assertions) : void
    {
        $this->node_if_false_assertions[$node] = $assertions;
    }

    /**
     * @param Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $node
     * @return array<int, \Psalm\Storage\Assertion>|null
     */
    public function getIfFalseAssertions(Node\Expr $node) : ?array
    {
        return $this->node_if_false_assertions[$node] ?? null;
    }

    public function isPureCompatible(Node\Expr $node) : bool
    {
        $node_type = $this->getType($node);

        return ($node_type && $node_type->reference_free) || $node->getAttribute('pure', false);
    }

    public function clearNodeOfTypeAndAssertions(Node\Expr $node) : void
    {
        unset($this->node_types[$node], $this->node_assertions[$node]);
    }
}
