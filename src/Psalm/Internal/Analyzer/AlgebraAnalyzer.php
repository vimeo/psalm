<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Internal\Clause;
use Psalm\CodeLocation;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\RedundantCondition;
use Psalm\IssueBuffer;
use Psalm\Type\Algebra;
use function array_intersect_key;
use function count;
use function array_unique;

/**
 * @internal
 */
class AlgebraAnalyzer
{
    /**
     * This looks to see if there are any clauses in one formula that contradict
     * clauses in another formula, or clauses that duplicate previous clauses
     *
     * e.g.
     * if ($a) { }
     * elseif ($a) { }
     *
     * @param  array<int, Clause>   $formula1
     * @param  array<int, Clause>   $formula2
     * @param  StatementsAnalyzer    $statements_analyzer,
     * @param  PhpParser\Node       $stmt
     * @param  array<string, bool>  $new_assigned_var_ids
     *
     * @return void
     */
    public static function checkForParadox(
        array $formula1,
        array $formula2,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node $stmt,
        array $new_assigned_var_ids
    ) {
        try {
            $negated_formula2 = Algebra::negateFormula($formula2);
        } catch (\Psalm\Exception\ComplicatedExpressionException $e) {
            return;
        }

        $formula1_hashes = [];

        foreach ($formula1 as $formula1_clause) {
            $formula1_hashes[$formula1_clause->hash] = true;
        }

        $formula2_hashes = [];

        foreach ($formula2 as $formula2_clause) {
            $hash = $formula2_clause->hash;

            if (!$formula2_clause->generated
                && !$formula2_clause->wedge
                && $formula2_clause->reconcilable
                && (isset($formula1_hashes[$hash]) || isset($formula2_hashes[$hash]))
                && !array_intersect_key($new_assigned_var_ids, $formula2_clause->possibilities)
            ) {
                if (IssueBuffer::accepts(
                    new RedundantCondition(
                        $formula2_clause . ' has already been asserted',
                        new CodeLocation($statements_analyzer, $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            foreach ($formula2_clause->possibilities as $key => $values) {
                if (!$formula2_clause->generated
                    && count($values) > 1
                    && !isset($new_assigned_var_ids[$key])
                    && count(array_unique($values)) < count($values)
                ) {
                    if (IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            'Found a redundant condition when evaluating assertion (' . $formula2_clause . ')',
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            $formula2_hashes[$hash] = true;
        }

        // remove impossible types
        foreach ($negated_formula2 as $clause_a) {
            if (count($negated_formula2) === 1) {
                foreach ($clause_a->possibilities as $key => $values) {
                    if (count($values) > 1
                        && !isset($new_assigned_var_ids[$key])
                        && count(array_unique($values)) < count($values)
                    ) {
                        if (IssueBuffer::accepts(
                            new RedundantCondition(
                                'Found a redundant condition when evaluating ' . $key,
                                new CodeLocation($statements_analyzer, $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }

            if (!$clause_a->reconcilable || $clause_a->wedge) {
                continue;
            }

            foreach ($formula1 as $clause_b) {
                if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
                    continue;
                }

                $clause_a_contains_b_possibilities = true;

                foreach ($clause_b->possibilities as $key => $keyed_possibilities) {
                    if (!isset($clause_a->possibilities[$key])) {
                        $clause_a_contains_b_possibilities = false;
                        break;
                    }

                    if ($clause_a->possibilities[$key] != $keyed_possibilities) {
                        $clause_a_contains_b_possibilities = false;
                        break;
                    }
                }

                if ($clause_a_contains_b_possibilities) {
                    if (IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            'Encountered a paradox when evaluating the conditionals ('
                                . $clause_a . ') and (' . $clause_b . ')',
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }
            }
        }
    }
}
