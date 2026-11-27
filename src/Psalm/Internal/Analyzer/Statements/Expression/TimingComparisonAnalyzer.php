<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function strtolower;

/**
 * `==`, `===` and strcmp() return as soon as two strings differ, so comparing a secret key with user input takes a
 * time that tells how much of the key the input matches, and an attacker can guess the key byte by byte. The two
 * strings a comparison compares are sinks of secrets and of user input, and a comparison with a secret on one side
 * and user input on the other is reported once the taint graph is resolved (see TaintFlowGraph::addComparisonSinks()).
 * hash_equals() takes the same time whatever the strings are.
 *
 * @internal
 */
final class TimingComparisonAnalyzer
{
    /** The functions comparing two of their arguments byte by byte => the names of their parameters */
    private const COMPARING_FUNCTIONS = [
        'strcasecmp' => ['string1', 'string2'],
        'strcmp' => ['string1', 'string2'],
        'strncasecmp' => ['string1', 'string2'],
        'strncmp' => ['string1', 'string2'],
        'substr_compare' => ['haystack', 'needle'],
    ];

    public static function analyzeComparison(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
    ): void {
        self::addSinks($statements_analyzer, $stmt, $stmt->getOperatorSigil(), $stmt->left, $stmt->right);
    }

    /**
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function analyzeFunctionCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        array $args,
    ): void {
        $param_names = self::COMPARING_FUNCTIONS[strtolower($function_id)] ?? null;
        if ($param_names === null) {
            return;
        }

        $first = self::getArg($args, 0, $param_names[0]);
        $second = self::getArg($args, 1, $param_names[1]);

        if ($first !== null && $second !== null) {
            self::addSinks($statements_analyzer, $stmt, $function_id, $first, $second);
        }
    }

    private static function addSinks(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $comparison,
        string $label,
        PhpParser\Node\Expr $first,
        PhpParser\Node\Expr $second,
    ): void {
        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();
        if ($graph === null) {
            return;
        }

        $first_type = $statements_analyzer->node_data->getType($first);
        $second_type = $statements_analyzer->node_data->getType($second);

        if ($first_type === null
            || $second_type === null
            || !$first_type->parent_nodes
            || !$second_type->parent_nodes
            || !self::mayBeUnknownString($first_type)
            || !self::mayBeUnknownString($second_type)
        ) {
            return;
        }

        $location = new CodeLocation($statements_analyzer->getSource(), $comparison);
        $taints = TaintFlowGraph::COMPARED_SECRETS | $statements_analyzer->getCodebase()->getInputTaints();

        $first_sink = DataFlowNode::getForAssignment($label . '#1', $location)->setTaints($taints);
        $second_sink = DataFlowNode::getForAssignment($label . '#2', $location)->setTaints($taints);
        $graph->addComparisonSinks($first_sink, $second_sink);

        foreach ($first_type->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $first_sink, 'arg');
        }

        foreach ($second_type->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $second_sink, 'arg');
        }
    }

    /**
     * Whether the value may be a string the analysis doesn't know: a literal compares with anything in a time that
     * tells nothing more than the result
     *
     * @psalm-pure
     */
    private static function mayBeUnknownString(Union $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomic) {
            if (($atomic instanceof TString && !$atomic instanceof TLiteralString)
                || $atomic instanceof TMixed
                || $atomic instanceof TScalar
                || $atomic instanceof TArrayKey
                || $atomic instanceof TTemplateParam
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * The argument given to the parameter at $offset named $name, if known
     *
     * @param list<PhpParser\Node\Arg> $args
     * @psalm-mutation-free
     */
    private static function getArg(array $args, int $offset, string $name): ?PhpParser\Node\Expr
    {
        foreach ($args as $i => $arg) {
            if ($arg->unpack) {
                return null;
            }

            if ($arg->name !== null ? $arg->name->name === $name : $i === $offset) {
                return $arg->value;
            }
        }

        return null;
    }
}
