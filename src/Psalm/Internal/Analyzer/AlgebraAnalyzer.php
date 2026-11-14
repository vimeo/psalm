<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Clause;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\RedundantCondition;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\InArray;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsNotType;
use Psalm\Storage\Assertion\NotInArray;
use Psalm\Type\Atomic\TNull;

use function array_intersect_key;
use function array_key_first;
use function count;
use function implode;
use function reset;

/**
 * @internal
 */
final class AlgebraAnalyzer
{
    /**
     * The variable of a clause that is only `isset($var)`
     *
     * @psalm-pure
     */
    private static function getIssetVarId(Clause $clause): ?string
    {
        $assertion = self::getSingleAssertion($clause);

        return $assertion !== null && $assertion[1] instanceof IsIsset ? $assertion[0] : null;
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
            $negated_formula2 = Algebra::negateFormula($formula_2);
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
                && !array_intersect_key($new_assigned_var_ids, $formula_2_clause->possibilities)
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

        // the condition is impossible when the established clauses imply every clause of its negation
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

        if ($first_implication === null || $implying_clause_count !== $negated_clause_count) {
            return;
        }

        [$negated_clause_2, $clause_1] = $first_implication;

        $mini_formula_2 = Algebra::negateFormula([$negated_clause_2]);

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
