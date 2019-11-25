<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use function spl_object_id;
use Psalm\Type\Union;

class NodeDataProvider implements \Psalm\NodeTypeProvider
{
    /** @var array<int, Union> */
    private $node_types = [];

    /** @var array<int, array<string, non-empty-list<non-empty-list<string>>>|null> */
    private $node_assertions = [];

    /** @var array<int, array<int, \Psalm\Storage\Assertion>> */
    private $node_if_true_assertions = [];

    /** @var array<int, array<int, \Psalm\Storage\Assertion>> */
    private $node_if_false_assertions = [];

    /** @var bool */
    public $cache_assertions = true;

    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public function setType($node, Union $type) : void
    {
        $this->node_types[spl_object_id($node)] = $type;
    }

    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public function getType($node) : ?Union
    {
        return $this->node_types[spl_object_id($node)] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr $node
     * @param array<string, non-empty-list<non-empty-list<string>>>|null $assertions
     */
    public function setAssertions($node, ?array $assertions) : void
    {
        if (!$this->cache_assertions) {
            return;
        }

        $this->node_assertions[spl_object_id($node)] = $assertions;
    }

    /**
     * @param PhpParser\Node\Expr $node
     * @return array<string, non-empty-list<non-empty-list<string>>>|null
     */
    public function getAssertions($node) : ?array
    {
        if (!$this->cache_assertions) {
            return null;
        }

        return $this->node_assertions[spl_object_id($node)] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @param array<int, \Psalm\Storage\Assertion> $assertions
     */
    public function setIfTrueAssertions($node, array $assertions) : void
    {
        $this->node_if_true_assertions[spl_object_id($node)] = $assertions;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @return array<int, \Psalm\Storage\Assertion>|null
     */
    public function getIfTrueAssertions($node) : ?array
    {
        return $this->node_if_true_assertions[spl_object_id($node)] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @param array<int, \Psalm\Storage\Assertion> $assertions
     */
    public function setIfFalseAssertions($node, array $assertions) : void
    {
        $this->node_if_false_assertions[spl_object_id($node)] = $assertions;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @return array<int, \Psalm\Storage\Assertion>|null
     */
    public function getIfFalseAssertions($node) : ?array
    {
        return $this->node_if_false_assertions[spl_object_id($node)] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr $node
     */
    public function isPureCompatible($node) : bool
    {
        $node_type = self::getType($node);

        return ($node_type && $node_type->external_mutation_free) || isset($node->pure);
    }

    /**
     * @param PhpParser\Node\Expr $node
     */
    public function clearNodeOfTypeAndAssertions($node) : void
    {
        $id = spl_object_id($node);

        unset($this->node_types[$id], $this->node_assertions[$id]);
    }
}
