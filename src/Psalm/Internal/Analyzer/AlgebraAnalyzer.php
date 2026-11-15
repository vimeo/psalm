<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Clause;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\RedundantCondition;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\InArray;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsNotType;
use Psalm\Storage\Assertion\NotInArray;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_any;
use function array_filter;
use function array_key_first;
use function array_keys;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function preg_match;
use function preg_quote;
use function reset;
use function sort;
use function spl_object_id;
use function strpbrk;

/**
 * @internal
 */
final class AlgebraAnalyzer
{
    /**
     * Context::removeReconciledClauses() for the variables a condition's reconciliation changed, keeping the clauses
     * the new types do not express: `$x` truthy makes an `int|null` an `int`, which can still be falsy
     *
     * @param list<Clause> $clauses
     * @param array<string, bool> $changed_var_ids
     * @param array<string, Union> $vars_in_scope
     * @return array{list<Clause>, list<Clause>}
     */
    public static function removeReconciledClauses(
        array $clauses,
        array $changed_var_ids,
        array $vars_in_scope,
        StatementsAnalyzer $statements_analyzer,
    ): array {
        [$included_clauses, $rejected_clauses] = Context::removeReconciledClauses($clauses, $changed_var_ids);

        $expressed_clauses = [];

        foreach ($rejected_clauses as $clause) {
            if (self::isExpressedByTypes($clause, $vars_in_scope, $statements_analyzer)) {
                $expressed_clauses[] = $clause;
            } else {
                $included_clauses[] = $clause;
            }
        }

        return [$included_clauses, $expressed_clauses];
    }

    /**
     * Whether the type of the variable makes the clause certain. Types can't say that an int or a float is not zero,
     * so only a lone truthiness assertion is known not to be, when its negation is still possible: the negations of
     * other assertions are not all exact
     *
     * @param array<string, Union> $vars_in_scope
     */
    private static function isExpressedByTypes(
        Clause $clause,
        array $vars_in_scope,
        StatementsAnalyzer $statements_analyzer,
    ): bool {
        $assertion = self::getSingleAssertion($clause);

        if ($assertion === null || !($assertion[1] instanceof Truthy || $assertion[1] instanceof Falsy)) {
            return true;
        }

        [$var_id, $possibility] = $assertion;

        $type = $vars_in_scope[$var_id] ?? null;

        // reconciling a type variable records bounds on it
        if ($type === null
            || array_any(
                $type->getAtomicTypes(),
                static fn(Atomic $atomic): bool => $atomic instanceof TTypeVariable,
            )
        ) {
            return true;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_OK;

        $negated_type = AssertionReconciler::reconcile(
            $possibility->getNegation(),
            $type,
            $var_id,
            $statements_analyzer,
            false,
            $statements_analyzer->getTemplateTypeMap() ?? [],
            null,
            [],
            $failed_reconciliation,
        );

        $codebase = $statements_analyzer->getCodebase();

        // the negation is only known to be possible when it narrows the type: the reconciler may return the asserted
        // type or an empty union instead of never when it is impossible, or the type unchanged when it can't tell
        return $failed_reconciliation === Reconciler::RECONCILIATION_EMPTY
            || $negated_type->isUnionEmpty()
            || $negated_type->isNever()
            || !UnionTypeComparator::isContainedBy($codebase, $negated_type, $type)
            || UnionTypeComparator::isContainedBy($codebase, $type, $negated_type);
    }

    /**
     * The variable of a clause that is only `isset($var)`, when it is a plain local one: `isset($a['k'])` and
     * `isset($o->p)` can be true while the value is null (`ArrayAccess::offsetExists()`, `__isset()`)
     *
     * @psalm-pure
     */
    private static function getIssetVarId(Clause $clause): ?string
    {
        $assertion = self::getSingleAssertion($clause);

        return $assertion !== null
            && $assertion[1] instanceof IsIsset
            && $assertion[0][0] === '$'
            && strpbrk($assertion[0], '[-:') === false
                ? $assertion[0]
                : null;
    }

    /**
     * The variable of a clause that is only `$var !== null`
     *
     * @psalm-pure
     */
    private static function getNotNullVarId(Clause $clause): ?string
    {
        $assertion = self::getSingleAssertion($clause);

        return $assertion !== null
            && $assertion[1] instanceof IsNotType
            && $assertion[1]->type instanceof TNull
                ? $assertion[0]
                : null;
    }

    /**
     * The variable and the assertion of a clause that has no other possibility
     *
     * @return array{string, Assertion}|null
     * @psalm-pure
     */
    private static function getSingleAssertion(Clause $clause): ?array
    {
        if (count($clause->possibilities) !== 1) {
            return null;
        }

        $var_id = array_key_first($clause->possibilities);

        return count($clause->possibilities[$var_id]) === 1
            ? [$var_id, reset($clause->possibilities[$var_id])]
            : null;
    }

    /**
     * Whether the clause tests one of the variables, an item or property of one, or an item whose key is one
     * (`$a[$i]` for `$i`)
     *
     * @param array<string, int> $var_ids
     * @psalm-pure
     */
    private static function testsAnyVar(Clause $clause, array $var_ids): bool
    {
        foreach ($clause->possibilities as $key => $_) {
            if (isset($var_ids[$key])) {
                return true;
            }

            foreach ($var_ids as $var_id => $_) {
                if (preg_match('/' . preg_quote($var_id, '/') . '[\]\[\-]/', $key)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * `in-array-X` doesn't say which array the value was looked up in: two equal ones are not a repetition
     *
     * @psalm-pure
     */
    private static function hasInArrayPossibility(Clause $clause): bool
    {
        foreach ($clause->possibilities as $keyed_possibilities) {
            foreach ($keyed_possibilities as $possibility) {
                if ($possibility instanceof InArray || $possibility instanceof NotInArray) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * This looks to see if there are any clauses in one formula that contradict
     * clauses in another formula, or clauses that duplicate previous clauses
     *
     * e.g.
     * if ($a) { }
     * elseif ($a) { }
     *
     * @param  list<Clause>   $formula_1
     * @param  list<Clause>   $formula_2
     * @param  array<string, int>  $new_assigned_var_ids
     */
    public static function checkForParadox(
        array $formula_1,
        array $formula_2,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node $stmt,
        array $new_assigned_var_ids,
    ): void {
        try {
            $negated_formula2 = Algebra::negateFormula($formula_2, true);
        } catch (ComplicatedExpressionException) {
            return;
        }

        $formula_1_hashes = [];

        foreach ($formula_1 as $formula_1_clause) {
            $formula_1_hashes[$formula_1_clause->hash] = true;
        }

        $formula_2_hashes = [];

        $isset_var_ids = [];

        foreach ($formula_1 as $formula_1_clause) {
            $isset_var_id = self::getIssetVarId($formula_1_clause);

            if ($isset_var_id !== null) {
                $isset_var_ids[$isset_var_id] = true;
            }
        }

        foreach ($formula_2 as $formula_2_clause) {
            $hash = $formula_2_clause->hash;

            $not_null_var_id = self::getNotNullVarId($formula_2_clause);

            if (!$formula_2_clause->generated
                && !$formula_2_clause->wedge
                && $formula_2_clause->reconcilable
                && (isset($formula_1_hashes[$hash])
                    || isset($formula_2_hashes[$hash])
                    // isset($x) already says that $x is not null
                    || ($not_null_var_id !== null && isset($isset_var_ids[$not_null_var_id])))
                && !self::testsAnyVar($formula_2_clause, $new_assigned_var_ids)
                && !self::hasInArrayPossibility($formula_2_clause)
            ) {
                IssueBuffer::maybeAdd(
                    new RedundantCondition(
                        $formula_2_clause . ' has already been asserted',
                        new CodeLocation($statements_analyzer, $stmt),
                        'already asserted ' . $formula_2_clause,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            $formula_2_hashes[$hash] = true;

            $isset_var_id = self::getIssetVarId($formula_2_clause);

            if ($isset_var_id !== null) {
                $isset_var_ids[$isset_var_id] = true;
            }
        }

        $implication = self::findImplication($formula_1, $negated_formula2);

        if ($implication !== null) {
            self::reportParadox($statements_analyzer, $stmt, $implication[0], $implication[1]);

            return;
        }

        // a condition with an operand of an || that can never be true is no paradox as a whole, but the operand is
        // dead code, unless the operands are the alternatives of one variable (`$a === 'x' || $a === 'y'`), of which
        // some may be excluded already
        $operands = self::getOrOperands($stmt);

        if ($operands === []) {
            return;
        }

        $operand_formulas = [];
        $operand_variable_sets = [];

        foreach ($operands as $operand) {
            $operand_id = spl_object_id($operand);

            try {
                $operand_formula = FormulaGenerator::getFormula(
                    $operand_id,
                    $operand_id,
                    $operand,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer,
                    $statements_analyzer->getCodebase(),
                );
                $operand_formulas[] = [$operand, Algebra::negateFormula($operand_formula, true)];
            } catch (ComplicatedExpressionException) {
                return;
            }

            $variables = [];

            foreach ($operand_formula as $clause) {
                $variables = [...$variables, ...array_keys($clause->possibilities)];
            }

            sort($variables);
            $operand_variable_sets[implode(',', array_unique($variables))] = true;
        }

        if (count($operand_variable_sets) === 1) {
            return;
        }

        $assigned_var_ids = [];

        foreach ($operand_formulas as [$operand, $negated_operand_formula]) {
            // an operand reads the variables it and the operands before it assign as assigned, not as established
            $assigned_var_ids += FormulaGenerator::getAssignedVarIds(
                $operand,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer->node_data,
            );

            $operand_formula_1 = $assigned_var_ids === [] ? $formula_1 : array_values(array_filter(
                $formula_1,
                static function (Clause $clause) use ($assigned_var_ids): bool {
                    foreach ($clause->possibilities as $key => $_) {
                        foreach (array_keys($assigned_var_ids) as $assigned_var_id) {
                            if (preg_match('/^' . preg_quote($assigned_var_id, '/') . '(\[|-|$)/', $key)) {
                                return false;
                            }
                        }
                    }

                    return true;
                },
            ));

            $operand_implication = self::findImplication($operand_formula_1, $negated_operand_formula);

            if ($operand_implication !== null) {
                self::reportParadox($statements_analyzer, $operand, $operand_implication[0], $operand_implication[1]);
            }
        }
    }

    /**
     * The operands of the `||` the expression is, as of `a || b || c`: none for any other expression
     *
     * @return list<PhpParser\Node\Expr>
     * @psalm-capabilities read-props
     */
    private static function getOrOperands(PhpParser\Node $expr): array
    {
        if (!$expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            && !$expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            return [];
        }

        $operands = [];

        foreach ([$expr->left, $expr->right] as $side) {
            $side_operands = self::getOrOperands($side);
            $operands = [...$operands, ...($side_operands !== [] ? $side_operands : [$side])];
        }

        return $operands;
    }

    /**
     * The condition is impossible when the established clauses imply every clause of its negation: the first of the
     * negated clauses and the established clause implying it, or null
     *
     * @param list<Clause> $formula_1
     * @param list<Clause> $negated_formula2
     * @return array{Clause, Clause}|null
     * @psalm-pure
     */
    private static function findImplication(array $formula_1, array $negated_formula2): ?array
    {
        $implying_clause_count = 0;
        $negated_clause_count = 0;
        $first_implication = null;

        foreach ($negated_formula2 as $negated_clause_2) {
            if (!$negated_clause_2->reconcilable || $negated_clause_2->wedge) {
                continue;
            }

            ++$negated_clause_count;

            foreach ($formula_1 as $clause_1) {
                if ($negated_clause_2 === $clause_1 || !$clause_1->reconcilable || $clause_1->wedge) {
                    continue;
                }

                $negated_clause_2_contains_1_possibilities = true;

                foreach ($clause_1->possibilities as $key => $keyed_possibilities) {
                    if (!isset($negated_clause_2->possibilities[$key])) {
                        $negated_clause_2_contains_1_possibilities = false;
                        break;
                    }

                    if ($negated_clause_2->possibilities[$key] != $keyed_possibilities) {
                        $negated_clause_2_contains_1_possibilities = false;
                        break;
                    }
                    foreach ($keyed_possibilities as $possibility) {
                        if ($possibility instanceof InArray || $possibility instanceof NotInArray) {
                            $negated_clause_2_contains_1_possibilities = false;
                            break;
                        }
                    }
                }

                if ($negated_clause_2_contains_1_possibilities) {
                    ++$implying_clause_count;
                    $first_implication ??= [$negated_clause_2, $clause_1];

                    break;
                }
            }
        }

        return $first_implication !== null && $implying_clause_count === $negated_clause_count
            ? $first_implication
            : null;
    }

    private static function reportParadox(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node $stmt,
        Clause $negated_clause_2,
        Clause $clause_1,
    ): void {
        $mini_formula_2 = Algebra::negateFormula([$negated_clause_2], true);

        if (!$mini_formula_2[0]->wedge) {
            if (count($mini_formula_2) > 1) {
                $paradox_message = 'Condition ((' . implode(') && (', $mini_formula_2) . '))'
                    . ' contradicts a previously-established condition (' . $clause_1 . ')';
            } else {
                $paradox_message = 'Condition (' . $mini_formula_2[0] . ')'
                    . ' contradicts a previously-established condition (' . $clause_1 . ')';
            }
        } else {
            $paradox_message = 'Condition not(' . $negated_clause_2 . ')'
                . ' contradicts a previously-established condition (' . $clause_1 . ')';
        }

        IssueBuffer::maybeAdd(
            new ParadoxicalCondition(
                $paradox_message,
                new CodeLocation($statements_analyzer, $stmt),
            ),
            $statements_analyzer->getSuppressedIssues(),
        );
    }
}
