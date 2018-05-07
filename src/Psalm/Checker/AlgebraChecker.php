<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Checker\Statements\Expression\AssertionFinder;
use Psalm\Clause;
use Psalm\CodeLocation;
use Psalm\FileSource;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\RedundantCondition;
use Psalm\IssueBuffer;
use Psalm\Type\Algebra;

class AlgebraChecker
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
     * @param  StatementsChecker    $statements_checker,
     * @param  PhpParser\Node       $stmt
     * @param  array<string, bool>  $new_assigned_var_ids
     *
     * @return void
     */
    public static function checkForParadox(
        array $formula1,
        array $formula2,
        StatementsChecker $statements_checker,
        PhpParser\Node $stmt,
        array $new_assigned_var_ids
    ) {
        $negated_formula2 = Algebra::negateFormula($formula2);

        // remove impossible types
        foreach ($negated_formula2 as $clause_a) {
            if (count($negated_formula2) === 1) {
                foreach ($clause_a->possibilities as $key => $values) {
                    if (count($values) > 1
                        && count(array_unique($values)) < count($values)
                        && !isset($new_assigned_var_ids[$key])
                    ) {
                        if (IssueBuffer::accepts(
                            new RedundantCondition(
                                'Found a redundant condition when evaluating ' . $key,
                                new CodeLocation($statements_checker, $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
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
                            new CodeLocation($statements_checker, $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }
            }
        }
    }
}
