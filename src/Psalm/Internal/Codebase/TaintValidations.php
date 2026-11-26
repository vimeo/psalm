<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\MatchesPattern;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

use function array_keys;
use function preg_match;
use function sort;
use function strtolower;

/**
 * What a validator checks a value against (a pattern), recorded for the call (see Codebase::addTaintValidation()),
 * for the checks of its result: once they tell the validation passed, the variable holding the value can't carry the
 * taints the pattern removes, as long as it holds the same value.
 *
 * A result is either a validator, which `fails()` when the validation failed and `passes()` when it passed, or a list
 * of violations, empty when it passed (`count($violations) > 0`, `$violations->count() === 0`, ...).
 *
 * @psalm-type Validation = array{string, list<string>, int, 'validator'|'violations'}
 * @internal
 */
final class TaintValidations
{
    /**
     * Records that the result of a validation tells whether the value matches a pattern removing the taints
     *
     * @param 'validator'|'violations' $kind
     */
    public static function record(
        StatementsAnalyzer $statements_analyzer,
        Expr\CallLike $call,
        Expr $value,
        int $removed_taints,
        string $kind,
    ): void {
        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();

        if ($graph === null || $removed_taints === 0) {
            return;
        }

        // a value cast to a string has the taints of what is cast
        if ($value instanceof Expr\Cast\String_) {
            foreach ($statements_analyzer->node_data->getType($value->expr)?->getAtomicTypes() ?? [] as $atomic) {
                if (!$atomic instanceof Scalar && !$atomic instanceof TNull) {
                    return;
                }
            }

            $value = $value->expr;
        }

        $var_id = ExpressionIdentifier::getExtendedVarId(
            $value,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );
        $value_type = $statements_analyzer->node_data->getType($value);

        // a property, an element or a reference can be changed without the variable being assigned
        if ($var_id === null
            || !preg_match('/^\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $var_id)
            || $value_type === null
            || $value_type->by_ref
            || $value_type->parent_nodes === []
        ) {
            return;
        }

        $node_ids = array_keys($value_type->parent_nodes);
        sort($node_ids);

        // the analysis gives the value of the call its nodes after the plugins: the call is told by its location
        $graph->addTaintValidationOfCall(
            self::getCallKey($statements_analyzer, $call),
            [$var_id, $node_ids, $removed_taints, $kind],
        );
    }

    /**
     * Records the validations the value assigned holds for the node of the variable it is assigned to: those of the
     * validation it is the result of, or of the variable it is
     */
    public static function recordAssignment(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        Expr $assigned,
        Union $assigned_type,
        DataFlowNode $var_node,
    ): void {
        $validations = self::getValidations($statements_analyzer, $graph, $assigned, $assigned_type);

        if ($validations !== []) {
            $graph->addTaintValidations($var_node, $validations);
        }
    }

    /**
     * The assertions a condition on the result of a validation makes: the value matches the pattern where the
     * validation passed
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     * @psalm-capabilities read-props
     */
    public static function getAssertions(StatementsAnalyzer $statements_analyzer, Expr $conditional): array
    {
        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();

        if ($graph === null) {
            return [];
        }

        $check = self::getCheck($conditional);

        if ($check === null) {
            return [];
        }

        [$result, $kind, $passed] = $check;
        $result_type = $statements_analyzer->node_data->getType($result);

        if ($result_type === null) {
            return [];
        }

        $assertions = [];
        $validations = self::getValidations($statements_analyzer, $graph, $result, $result_type);
        foreach ($validations as [$var_id, $node_ids, $removed, $of]) {
            if ($of === $kind) {
                $assertions[$var_id] = [[new MatchesPattern($removed, !$passed, $node_ids)]];
            }
        }

        return $assertions === [] ? [] : [$assertions];
    }

    /**
     * The validations an expression is the result of: the call making one, or a variable holding one
     *
     * @return list<array{string, list<string>, int, 'validator'|'violations'}>
     * @psalm-pure
     */
    private static function getValidations(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        Expr $expr,
        Union $type,
    ): array {
        if ($expr instanceof Expr\CallLike) {
            return $graph->getTaintValidationsOfCall(self::getCallKey($statements_analyzer, $expr));
        }

        return $type->parent_nodes === [] ? [] : $graph->getTaintValidations($type->parent_nodes);
    }

    /**
     * @psalm-pure
     */
    private static function getCallKey(StatementsAnalyzer $statements_analyzer, Expr\CallLike $call): string
    {
        $location = new CodeLocation($statements_analyzer->getSource(), $call);

        return $location->file_path . ':' . $location->raw_file_start . '-' . $location->raw_file_end;
    }

    /**
     * The result a condition checks, of which kind, and whether the condition is that the validation passed
     *
     * @return ?array{Expr, 'validator'|'violations', bool}
     * @psalm-capabilities read-props
     */
    private static function getCheck(Expr $conditional): ?array
    {
        if ($conditional instanceof Expr\MethodCall && $conditional->name instanceof Identifier) {
            $name = strtolower($conditional->name->name);

            if ($name === 'fails' || $name === 'passes') {
                return [$conditional->var, 'validator', $name === 'passes'];
            }

            if ($name === 'count') {
                return [$conditional->var, 'violations', false];
            }

            return null;
        }

        $count = self::getCountedResult($conditional);
        if ($count !== null) {
            return [$count, 'violations', false];
        }

        if (!$conditional instanceof Expr\BinaryOp) {
            return null;
        }

        $sides = [[$conditional->left, $conditional->right, false], [$conditional->right, $conditional->left, true]];
        foreach ($sides as [$counted, $number, $flipped]) {
            $result = self::getCountedResult($counted);

            if ($result === null || !$number instanceof Int_) {
                continue;
            }

            $value = $number->value;
            $passed = match (true) {
                $conditional instanceof Expr\BinaryOp\Identical, $conditional instanceof Expr\BinaryOp\Equal
                    => $value === 0 ? true : null,
                $conditional instanceof Expr\BinaryOp\NotIdentical, $conditional instanceof Expr\BinaryOp\NotEqual
                    => $value === 0 ? false : null,
                $conditional instanceof Expr\BinaryOp\Greater => !$flipped && $value === 0
                    ? false
                    : ($flipped && $value === 1 ? true : null),
                $conditional instanceof Expr\BinaryOp\GreaterOrEqual => !$flipped && $value === 1
                    ? false
                    : ($flipped && $value === 0 ? true : null),
                $conditional instanceof Expr\BinaryOp\Smaller => !$flipped && $value === 1
                    ? true
                    : ($flipped && $value === 0 ? false : null),
                $conditional instanceof Expr\BinaryOp\SmallerOrEqual => !$flipped && $value === 0
                    ? true
                    : ($flipped && $value === 1 ? false : null),
                default => null,
            };

            return $passed === null ? null : [$result, 'violations', $passed];
        }

        return null;
    }

    /**
     * The list of violations `count($list)` and `$list->count()` count
     *
     * @psalm-capabilities read-props
     */
    private static function getCountedResult(Expr $expr): ?Expr
    {
        if ($expr instanceof Expr\FuncCall
            && $expr->name instanceof Name
            && strtolower($expr->name->toString()) === 'count'
            && !$expr->isFirstClassCallable()
            && isset($expr->getArgs()[0])
            && !$expr->getArgs()[0]->unpack
        ) {
            return $expr->getArgs()[0]->value;
        }

        if ($expr instanceof Expr\MethodCall
            && $expr->name instanceof Identifier
            && strtolower($expr->name->name) === 'count'
        ) {
            return $expr->var;
        }

        return null;
    }
}
