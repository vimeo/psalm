<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeAbstract;
use Psalm\Internal\Codebase\StringContext;
use Psalm\NodeTypeProvider;
use Psalm\Storage\Assertion;
use Psalm\Storage\Possibilities;
use Psalm\Type\Union;
use SplObjectStorage;

/**
 * @internal
 */
final class NodeDataProvider implements NodeTypeProvider
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

    /** @var SplObjectStorage<Expr, list<string>> */
    private SplObjectStorage $node_literal_prefixes;

    /** @var SplObjectStorage<Expr, StringContext> */
    private SplObjectStorage $node_string_contexts;

    /** @var SplObjectStorage<Expr, true> */
    private SplObjectStorage $written_by_reference;

    /** @var SplObjectStorage<Expr, non-empty-array<string, true>> */
    private SplObjectStorage $vars_written_by_call;

    /**
     * Expressions analysed already, whose analysis is not repeated (see markEvaluated()), with how many times
     * they were marked
     *
     * @var SplObjectStorage<Expr, positive-int>
     */
    private SplObjectStorage $evaluated;

    /**
     * Array fetches whose offset was checked in this pass: a fetch that is read and written (`$a[$k]++`) is
     * analyzed twice, and its offset is reported once
     *
     * @var SplObjectStorage<Expr, true>
     */
    private SplObjectStorage $offset_checked;

    /** @var SplObjectStorage<If_, true> */
    private SplObjectStorage $impossible_implicit_elses;

    public bool $cache_assertions = true;

    /**
     * @psalm-capabilities read-props
     */
    public function __construct()
    {
        $this->node_types = new SplObjectStorage();
        $this->node_assertions = new SplObjectStorage();
        $this->node_if_true_assertions = new SplObjectStorage();
        $this->node_if_false_assertions = new SplObjectStorage();
        $this->node_literal_prefixes = new SplObjectStorage();
        $this->node_string_contexts = new SplObjectStorage();
        $this->written_by_reference = new SplObjectStorage();
        $this->vars_written_by_call = new SplObjectStorage();
        $this->evaluated = new SplObjectStorage();
        $this->offset_checked = new SplObjectStorage();
        $this->impossible_implicit_elses = new SplObjectStorage();
    }

    /**
     * Records whether the conditions of an if without an else matched every value of a variable, so that nothing
     * goes past the if without going through one of its branches
     */
    public function setImplicitElseImpossible(If_ $node, bool $impossible): void
    {
        if ($impossible) {
            $this->impossible_implicit_elses[$node] = true;
        } else {
            unset($this->impossible_implicit_elses[$node]);
        }
    }

    /**
     * @psalm-capabilities read-props
     */
    public function isImplicitElseImpossible(If_ $node): bool
    {
        return isset($this->impossible_implicit_elses[$node]);
    }

    /**
     * @param Expr|Name|Return_ $node
     */
    #[Override]
    public function setType(NodeAbstract $node, Union $type): void
    {
        $this->node_types[$node] = $type;
    }

    /**
     * @param Expr|Name|Return_ $node
     * @psalm-capabilities read-props
     */
    #[Override]
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
     * @psalm-capabilities read-props
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
     * @psalm-capabilities read-props
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
     * @psalm-capabilities read-props
     */
    public function getIfFalseAssertions(Expr $node): ?array
    {
        return $this->node_if_false_assertions[$node] ?? null;
    }

    /**
     * The literal strings a string concatenation or interpolation can start with (see
     * ConcatAnalyzer::getLiteralPrefixes())
     *
     * @param list<string> $prefixes
     */
    public function setLiteralPrefixes(Expr $node, array $prefixes): void
    {
        $this->node_literal_prefixes[$node] = $prefixes;
    }

    /**
     * @return list<string>|null
     * @psalm-capabilities read-props
     */
    public function getLiteralPrefixes(Expr $node): ?array
    {
        return $this->node_literal_prefixes[$node] ?? null;
    }

    /**
     * Where the text of a string concatenation or interpolation ends (see StringContext)
     */
    public function setStringContext(Expr $node, StringContext $context): void
    {
        $this->node_string_contexts[$node] = $context;
    }

    /**
     * @psalm-capabilities read-props
     */
    public function getStringContext(Expr $node): ?StringContext
    {
        return $this->node_string_contexts[$node] ?? null;
    }

    /**
     * Records an argument passed to a by-reference parameter, which the call may assign
     */
    public function markWrittenByReference(Expr $node): void
    {
        $this->written_by_reference[$node] = true;
    }

    /**
     * @psalm-capabilities read-props
     */
    public function isWrittenByReference(Expr $node): bool
    {
        return isset($this->written_by_reference[$node]);
    }

    /**
     * Records the variables of the scope that calling the callable may write through what a closure captured by
     * reference
     *
     * @param array<string, true> $var_ids
     */
    public function setVarIdsWrittenByCall(Expr $callable, array $var_ids): void
    {
        if ($var_ids) {
            $this->vars_written_by_call[$callable] = $var_ids;
        } else {
            unset($this->vars_written_by_call[$callable]);
        }
    }

    /**
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    public function getVarIdsWrittenByCall(Expr $callable): array
    {
        return $this->vars_written_by_call[$callable] ?? [];
    }

    /**
     * Until unmarkEvaluated(), ExpressionAnalyzer::analyze() keeps the type of these expressions instead of
     * analysing them again: their side effects happen once
     *
     * @param list<Expr> $nodes
     */
    public function markEvaluated(array $nodes): void
    {
        foreach ($nodes as $node) {
            $this->evaluated[$node] = ($this->evaluated[$node] ?? 0) + 1;
        }
    }

    /**
     * @param list<Expr> $nodes
     */
    public function unmarkEvaluated(array $nodes): void
    {
        foreach ($nodes as $node) {
            $count = $this->evaluated[$node] ?? 0;

            if ($count > 1) {
                $this->evaluated[$node] = $count - 1;
            } else {
                unset($this->evaluated[$node]);
            }
        }
    }

    /**
     * @psalm-capabilities read-props
     */
    public function isEvaluated(Expr $node): bool
    {
        return $this->evaluated->count() !== 0
            && isset($this->evaluated[$node], $this->node_types[$node]);
    }

    public function isPureCompatible(Expr $node): bool
    {
        $node_type = $this->getType($node);

        return ($node_type && $node_type->reference_free) || $node->getAttribute('pure', false);
    }

    public function clearNodeOfTypeAndAssertions(Expr $node): void
    {
        unset(
            $this->node_types[$node],
            $this->node_assertions[$node],
            $this->node_literal_prefixes[$node],
            $this->node_string_contexts[$node],
            $this->offset_checked[$node],
        );
    }

    public function markOffsetChecked(Expr $node): void
    {
        $this->offset_checked[$node] = true;
    }

    /**
     * @psalm-capabilities read-props
     */
    public function isOffsetChecked(Expr $node): bool
    {
        return isset($this->offset_checked[$node]);
    }
}
