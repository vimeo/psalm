<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Storage\Assertion;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use UnexpectedValueException;

use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_values;
use function assert;
use function count;
use function in_array;
use function mt_rand;
use function reset;
use function strcspn;
use function substr;

/**
 * @internal
 */
final class Algebra
{
    /**
     * @param array<string, non-empty-list<non-empty-list<Assertion>>>  $all_types
     * @return array<string, non-empty-list<non-empty-list<Assertion>>>
     * @psalm-pure
     */
    public static function negateTypes(array $all_types): array
    {
        $negated_types = [];

        foreach ($all_types as $key => $anded_types) {
            if (count($anded_types) > 1) {
                $new_anded_types = [];

                foreach ($anded_types as $orred_types) {
                    if (count($orred_types) === 1) {
                        $new_anded_types[] = $orred_types[0]->getNegation();
                    } else {
                        continue 2;
                    }
                }

                assert($new_anded_types !== []);

                $negated_types[$key] = [$new_anded_types];
                continue;
            }

            $new_orred_types = [];

            foreach ($anded_types[0] as $orred_type) {
                $new_orred_types[] = [$orred_type->getNegation()];
            }

            $negated_types[$key] = $new_orred_types;
        }

        return $negated_types;
    }

    /**
     * This is a very simple simplification heuristic
     * for CNF formulae.
     *
     * It simplifies formulae:
     *     ($a) && ($a || $b) => $a
     *     (!$a) && (!$b) && ($a || $b || $c) => $c
     *
     * @param list<Clause>  $clauses
     * @return list<Clause>
     * @psalm-capabilities write-refs
     */
    public static function simplifyCNF(array $clauses): array
    {
        $clause_count = count($clauses);

        //65536 seems to be a significant threshold, when put at 65537, the code https://psalm.dev/r/216f362ea6 goes
        //from seconds in analysis to many minutes
        if ($clause_count > 65_536) {
            return [];
        }

        if ($clause_count > 50) {
            $all_has_unknown = true;

            foreach ($clauses as $clause) {
                $clause_has_unknown = false;
                foreach ($clause->possibilities as $key => $_) {
                    if ($key[0] === '*') {
                        $clause_has_unknown = true;
                        break;
                    }
                }

                if (!$clause_has_unknown) {
                    $all_has_unknown = false;
                    break;
                }
            }

            if ($all_has_unknown) {
                return $clauses;
            }
        }

        $cloned_clauses = [];

        // avoid strict duplicates, keeping the one negateFormula() doesn't leave out
        foreach ($clauses as $clause) {
            if (!$clause->implied || !isset($cloned_clauses[$clause->hash])) {
                $cloned_clauses[$clause->hash] = $clause;
            }
        }

        // remove impossible types. Implications take no part in this: they say nothing where their call wasn't
        // made, and must stay what negateFormula() keeps
        foreach ($cloned_clauses as $clause_a_hash => $clause_a) {
            if (!$clause_a->reconcilable || $clause_a->wedge || $clause_a->premise_var_id !== null) {
                continue;
            }
            $clause_a_keys = array_keys($clause_a->possibilities);

            if (count($clause_a->possibilities) !== 1 || count(array_values($clause_a->possibilities)[0]) !== 1) {
                foreach ($cloned_clauses as $clause_b) {
                    if ($clause_a === $clause_b
                        || !$clause_b->reconcilable
                        || $clause_b->wedge
                        || $clause_b->premise_var_id !== null
                    ) {
                        continue;
                    }

                    if ($clause_a_keys === array_keys($clause_b->possibilities)) {
                        $opposing_keys = [];

                        foreach ($clause_a->possibilities as $key => $a_possibilities) {
                            $b_possibilities = $clause_b->possibilities[$key];

                            if (array_keys($clause_a->possibilities[$key])
                                === array_keys($clause_b->possibilities[$key])
                            ) {
                                continue;
                            }

                            if (count($a_possibilities) === 1 && count($b_possibilities) === 1) {
                                if (reset($a_possibilities)->isNegationOf(reset($b_possibilities))) {
                                    $opposing_keys[] = $key;
                                    continue;
                                }
                            }

                            continue 2;
                        }

                        if (count($opposing_keys) === 1) {
                            unset($cloned_clauses[$clause_a_hash]);

                            $clause_a = $clause_a->removePossibilities($opposing_keys[0]);

                            if (!$clause_a) {
                                continue 2;
                            }

                            $cloned_clauses[$clause_a->hash] = $clause_a;
                        }
                    }
                }

                continue;
            }

            $clause_var = array_keys($clause_a->possibilities)[0];
            $only_type = array_pop(array_values($clause_a->possibilities)[0]);
            $negated_clause_type = $only_type->getNegation();
            $negated_clause_type_string = (string)$negated_clause_type;

            foreach ($cloned_clauses as $clause_b_hash => $clause_b) {
                if ($clause_a === $clause_b
                    || !$clause_b->reconcilable
                    || $clause_b->wedge
                    || $clause_b->premise_var_id !== null
                ) {
                    continue;
                }

                if (isset($clause_b->possibilities[$clause_var])) {
                    $unmatched = [];
                    $matched = [];

                    foreach ($clause_b->possibilities[$clause_var] as $k => $possible_type) {
                        if ((string)$possible_type === $negated_clause_type_string) {
                            $matched[] = $possible_type;
                        } else {
                            $unmatched[$k] = $possible_type;
                        }
                    }

                    if ($matched) {
                        $clause_var_possibilities = $unmatched;

                        unset($cloned_clauses[$clause_b_hash]);

                        if (!$clause_var_possibilities) {
                            $updated_clause = $clause_b->removePossibilities($clause_var);

                            if ($updated_clause) {
                                $cloned_clauses[$updated_clause->hash] = $updated_clause;
                            }
                        } else {
                            $updated_clause = $clause_b->addPossibilities(
                                $clause_var,
                                $clause_var_possibilities,
                            );

                            $cloned_clauses[$updated_clause->hash] = $updated_clause;
                        }
                    }
                }
            }
        }

        $simplified_clauses = [];

        foreach ($cloned_clauses as $clause_a) {
            $is_redundant = false;

            foreach ($cloned_clauses as $clause_b) {
                if ($clause_a === $clause_b
                    || !$clause_b->reconcilable
                    || $clause_b->wedge
                    || $clause_a->wedge
                    || $clause_a->premise_var_id !== null
                    || $clause_b->premise_var_id !== null
                ) {
                    continue;
                }

                if ($clause_a->contains($clause_b)) {
                    $is_redundant = true;
                    break;
                }
            }

            if (!$is_redundant) {
                $simplified_clauses[$clause_a->hash] = $clause_a;
            }
        }

        $clause_count = count($simplified_clauses);

        // simplify (A || X) && (!A || Y) && (X || Y)
        // to
        // simplify (A || X) && (!A || Y)
        // where X and Y are sets of orred terms, unless one is an implication: (X || Y) may say what nothing else does
        if ($clause_count > 2 && $clause_count < 256) {
            $clauses = array_values($simplified_clauses);
            for ($i = 0; $i < $clause_count; $i++) {
                $clause_a = $clauses[$i];
                for ($k = $i + 1; $k < $clause_count; $k++) {
                    $clause_b = $clauses[$k];
                    if ($clause_a->premise_var_id !== null || $clause_b->premise_var_id !== null) {
                        continue;
                    }

                    $common_keys = array_keys(
                        array_intersect_key($clause_a->possibilities, $clause_b->possibilities),
                    );
                    if ($common_keys) {
                        $common_negated_keys = [];
                        foreach ($common_keys as $common_key) {
                            if (count($clause_a->possibilities[$common_key]) === 1
                                && count($clause_b->possibilities[$common_key]) === 1
                                && reset($clause_a->possibilities[$common_key])->isNegationOf(
                                    reset($clause_b->possibilities[$common_key]),
                                )
                            ) {
                                $common_negated_keys[] = $common_key;
                            }
                        }

                        if ($common_negated_keys) {
                            $new_possibilities = [];

                            foreach ($clause_a->possibilities as $var_id => $possibilities) {
                                if (in_array($var_id, $common_negated_keys, true)) {
                                    continue;
                                }

                                if (!isset($new_possibilities[$var_id])) {
                                    $new_possibilities[$var_id] = $possibilities;
                                } else {
                                    $new_possibilities[$var_id] = array_merge(
                                        $new_possibilities[$var_id],
                                        $possibilities,
                                    );
                                }
                            }

                            foreach ($clause_b->possibilities as $var_id => $possibilities) {
                                if (in_array($var_id, $common_negated_keys, true)) {
                                    continue;
                                }

                                if (!isset($new_possibilities[$var_id])) {
                                    $new_possibilities[$var_id] = $possibilities;
                                } else {
                                    $new_possibilities[$var_id] = array_merge(
                                        $new_possibilities[$var_id],
                                        $possibilities,
                                    );
                                }
                            }

                            /** @psalm-suppress MixedArgumentTypeCoercion due I think to Psalm bug */
                            $conflict_clause = (new Clause(
                                $new_possibilities,
                                $clause_a->creating_conditional_id,
                                $clause_a->creating_conditional_id,
                                false,
                                true,
                                true,
                                [],
                            ));

                            unset($simplified_clauses[$conflict_clause->hash]);
                        }
                    }
                }
            }
        }

        return array_values($simplified_clauses);
    }

    /**
     * What implications conclude where single-assertion clauses rule out their premise: B from ($call || B) and
     * (!$call). An implication never says anything of its premise, as what a call asserts can't tell what it
     * returns.
     *
     * @param non-empty-list<Clause> $clauses
     * @return list<Clause>
     * @psalm-pure
     */
    private static function getImplicationConclusions(array $clauses): array
    {
        $implications = [];

        foreach ($clauses as $clause) {
            if ($clause->premise_var_id !== null && $clause->reconcilable) {
                $implications[$clause->premise_var_id][] = $clause;
            }
        }

        if (!$implications) {
            return [];
        }

        $premise_assertions = [];

        foreach ($clauses as $clause) {
            if ($clause->reconcilable && count($clause->possibilities) === 1) {
                foreach ($clause->possibilities as $var_id => $possibilities) {
                    if (count($possibilities) === 1 && isset($implications[$var_id])) {
                        $premise_assertions[$var_id][] = reset($possibilities);
                    }
                }
            }
        }

        $conclusions = [];

        foreach ($premise_assertions as $premise_var_id => $assertions) {
            foreach ($implications[$premise_var_id] as $implication) {
                $conclusion_possibilities = $implication->possibilities;
                $premise_possibilities = $conclusion_possibilities[$premise_var_id];
                unset($conclusion_possibilities[$premise_var_id]);

                if (count($premise_possibilities) !== 1 || count($conclusion_possibilities) !== 1) {
                    continue;
                }

                $premise_possibility = reset($premise_possibilities);

                foreach ($assertions as $assertion) {
                    if ($premise_possibility->isNegationOf($assertion)) {
                        $conclusions[] = new Clause(
                            $conclusion_possibilities,
                            $implication->creating_conditional_id,
                            $implication->creating_object_id,
                            generated: $implication->generated,
                            redefined_vars: $implication->redefined_vars,
                        );

                        break;
                    }
                }
            }
        }

        return $conclusions;
    }

    /**
     * Look for clauses with only one possible value
     *
     * doesn't infer the "unset" correctly
     *
     * @psalm-suppress MoreSpecificReturnType
     * @param list<Clause>  $clauses
     * @param array<string, bool> $cond_referenced_var_ids
     * @param array<string, array<int, array<int, Assertion>>> $active_truths
     * @return array<string, list<list<Assertion>>>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public static function getTruthsFromFormula(
        array $clauses,
        ?int $creating_conditional_id = null,
        array &$cond_referenced_var_ids = [],
        array &$active_truths = [],
    ): array {
        $truths = [];
        $active_truths = [];

        if ($clauses === []) {
            return [];
        }

        $conclusions = self::getImplicationConclusions($clauses);

        if ($conclusions) {
            $clauses = [...$clauses, ...$conclusions];
        }

        foreach ($clauses as $clause) {
            if (!$clause->reconcilable || count($clause->possibilities) !== 1) {
                continue;
            }

            foreach ($clause->possibilities as $var => $possible_types) {
                if ($var[0] === '*') {
                    continue;
                }

                // if there's only one possible type, return it
                if (count($possible_types) === 1) {
                    $possible_type = array_pop($possible_types);

                    if (isset($truths[$var]) && !isset($clause->redefined_vars[$var])) {
                        $truths[$var][] = [$possible_type];
                    } else {
                        $truths[$var] = [[$possible_type]];
                    }

                    if ($creating_conditional_id && $creating_conditional_id === $clause->creating_conditional_id) {
                        if (!isset($active_truths[$var])) {
                            $active_truths[$var] = [];
                        }

                        $active_truths[$var][count($truths[$var]) - 1] = [$possible_type];
                    }
                } else {
                    // if there's only one active clause, return all the non-negation clause members ORed together
                    $things_that_can_be_said = [];

                    foreach ($possible_types as $assertion) {
                        $things_that_can_be_said[(string)$assertion] = $assertion;
                    }

                    if ($clause->generated && count($possible_types) > 1) {
                        unset($cond_referenced_var_ids[$var]);
                    }

                    $truths[$var] = [array_values($things_that_can_be_said)];

                    if ($creating_conditional_id && $creating_conditional_id === $clause->creating_conditional_id) {
                        $active_truths[$var] = [array_values($things_that_can_be_said)];
                    }
                }
            }
        }

        foreach ($truths as $var => $anded_types) {
            $has_list_or_array = false;
            foreach ($anded_types as $orred_types) {
                foreach ($orred_types as $assertion) {
                    if ($assertion->isNegation()) {
                        continue;
                    }

                    if (!isset($assertion->type)) {
                        continue;
                    }

                    if ($assertion->type instanceof TArray
                        || $assertion->type instanceof TKeyedArray) {
                        $has_list_or_array = true;
                        // list/array are collapsed, therefore there can only be 1 and we can abort
                        // otherwise we would have to remove them all individually
                        // e.g. array<string, string> cannot be array<int, float>
                        break 2;
                    }
                }
            }

            if ($has_list_or_array === false) {
                continue;
            }

            foreach ($anded_types as $key => $orred_types) {
                foreach ($orred_types as $index => $assertion) {
                    // we only need to check negations
                    // due to type collapsing, any negations for arrays are irrelevant
                    if (!$assertion->isNegation()) {
                        continue;
                    }

                    if (!isset($assertion->type)) {
                        continue;
                    }

                    if ($assertion->type instanceof TArray
                        || $assertion->type instanceof TKeyedArray) {
                        unset($truths[$var][$key][$index]);
                    }
                }

                /**
                 * doesn't infer the "unset" correctly
                 *
                 * @psalm-suppress DocblockTypeContradiction
                 */
                if ($truths[$var][$key] === []) {
                    unset($truths[$var][$key]);
                } else {
                    /**
                     * doesn't infer the "unset" correctly
                     *
                     * @psalm-suppress RedundantFunctionCallGivenDocblockType
                     */
                    $truths[$var][$key] = array_values($truths[$var][$key]);
                }
            }
        }

        /** @psalm-suppress LessSpecificReturnStatement */
        return $truths;
    }

    /**
     * @param non-empty-list<Clause>  $clauses
     * @return list<Clause>
     * @psalm-pure
     */
    public static function groupImpossibilities(array $clauses): array
    {
        $complexity = 1;

        $seed_clauses = [];

        $clause = array_pop($clauses);

        if (!$clause->wedge) {
            if ($clause->impossibilities === null) {
                throw new UnexpectedValueException('$clause->impossibilities should not be null');
            }

            foreach ($clause->impossibilities as $var => $impossible_types) {
                foreach ($impossible_types as $impossible_type) {
                    $seed_clause = new Clause(
                        [$var => [(string)$impossible_type => $impossible_type]],
                        $clause->creating_conditional_id,
                        $clause->creating_object_id,
                    );

                    $seed_clauses[] = $seed_clause;

                    ++$complexity;
                }
            }
        }

        if (!$clauses || !$seed_clauses) {
            return $seed_clauses;
        }

        $complexity_upper_bound = count($seed_clauses);

        foreach ($clauses as $clause) {
            $i = 0;
            foreach ($clause->possibilities as $p) {
                $i += count($p);
            }

            $complexity_upper_bound *= $i;

            if ($complexity_upper_bound > 20_000) {
                throw new ComplicatedExpressionException();
            }
        }

        while ($clauses) {
            $clause = array_pop($clauses);

            $new_clauses = [];

            foreach ($seed_clauses as $grouped_clause) {
                if ($clause->impossibilities === null) {
                    throw new UnexpectedValueException('$clause->impossibilities should not be null');
                }

                foreach ($clause->impossibilities as $var => $impossible_types) {
                    foreach ($impossible_types as $impossible_type) {
                        $new_clause_possibilities = $grouped_clause->possibilities;

                        if (isset($new_clause_possibilities[$var])) {
                            $impossible_type_string = (string)$impossible_type;
                            $new_clause_possibilities[$var][$impossible_type_string] = $impossible_type;

                            foreach ($new_clause_possibilities[$var] as $ak => $av) {
                                foreach ($new_clause_possibilities[$var] as $bk => $bv) {
                                    if ($ak == $bk) {
                                        break;
                                    }

                                    if ($ak !== $impossible_type_string && $bk !== $impossible_type_string) {
                                        continue;
                                    }

                                    if ($av->isNegationOf($bv)) {
                                        break 3;
                                    }
                                }
                            }
                        } else {
                            $new_clause_possibilities[$var] = [(string)$impossible_type => $impossible_type];
                        }

                        $new_clause = new Clause(
                            $new_clause_possibilities,
                            $grouped_clause->creating_conditional_id,
                            $clause->creating_object_id,
                            false,
                            true,
                            true,
                            [],
                        );

                        $new_clauses[] = $new_clause;

                        ++$complexity;

                        if ($complexity > 20_000) {
                            throw new ComplicatedExpressionException();
                        }
                    }
                }
            }

            $seed_clauses = $new_clauses;
        }

        return $seed_clauses;
    }

    /**
     * @param list<Clause>  $left_clauses
     * @param list<Clause>  $right_clauses
     * @return list<Clause>
     * @psalm-pure
     */
    public static function combineOredClauses(
        array $left_clauses,
        array $right_clauses,
        int $conditional_object_id,
    ): array {
        if (count($left_clauses) > 60_000 || count($right_clauses) > 60_000) {
            return [];
        }

        $left_implications = array_filter(
            $left_clauses,
            static fn(Clause $clause): bool => $clause->premise_var_id !== null,
        );
        $right_implications = array_filter(
            $right_clauses,
            static fn(Clause $clause): bool => $clause->premise_var_id !== null,
        );

        if ($left_implications || $right_implications) {
            return [
                ...self::combineOredClauses(
                    array_values(array_diff_key($left_clauses, $left_implications)),
                    array_values(array_diff_key($right_clauses, $right_implications)),
                    $conditional_object_id,
                ),
                ...array_values($left_implications),
                ...array_values($right_implications),
            ];
        }

        $clauses = [];

        $all_wedges = true;
        $has_wedge = false;

        foreach ($left_clauses as $left_clause) {
            foreach ($right_clauses as $right_clause) {
                $all_wedges = $all_wedges && ($left_clause->wedge && $right_clause->wedge);
                $has_wedge = $has_wedge || ($left_clause->wedge && $right_clause->wedge);
            }
        }

        if ($all_wedges) {
            return [new Clause([], $conditional_object_id, $conditional_object_id, true)];
        }

        foreach ($left_clauses as $left_clause) {
            foreach ($right_clauses as $right_clause) {
                if ($left_clause->wedge && $right_clause->wedge) {
                    // handled below
                    continue;
                }

                /** @var  array<string, non-empty-array<string, Assertion>> */
                $possibilities = [];

                $can_reconcile = true;

                if ($left_clause->wedge ||
                    $right_clause->wedge ||
                    !$left_clause->reconcilable ||
                    !$right_clause->reconcilable
                ) {
                    $can_reconcile = false;
                }

                foreach ($left_clause->possibilities as $var => $possible_types) {
                    // the right side assigned the variable, or the variable holding the array or object
                    if (isset($right_clause->redefined_vars[$var])
                        || isset($right_clause->redefined_vars[substr($var, 0, strcspn($var, '[-'))])
                    ) {
                        continue;
                    }

                    if (isset($possibilities[$var])) {
                        $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
                    } else {
                        $possibilities[$var] = $possible_types;
                    }
                }

                foreach ($right_clause->possibilities as $var => $possible_types) {
                    if (isset($possibilities[$var])) {
                        $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
                    } else {
                        $possibilities[$var] = $possible_types;
                    }
                }

                foreach ($possibilities as $var_possibilities) {
                    if (count($var_possibilities) === 2) {
                        $vals = array_values($var_possibilities);
                        /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
                        if ($vals[0]->isNegationOf($vals[1])) {
                            continue 2;
                        }
                    }
                }

                $creating_conditional_id =
                    $right_clause->creating_conditional_id === $left_clause->creating_conditional_id
                    ? $right_clause->creating_conditional_id
                    : $conditional_object_id;

                $clauses[] = new Clause(
                    $possibilities,
                    $creating_conditional_id,
                    $creating_conditional_id,
                    false,
                    $can_reconcile,
                    $right_clause->generated
                        || $left_clause->generated
                        || count($left_clauses) > 1
                        || count($right_clauses) > 1,
                    [],
                    $left_clause->implied || $right_clause->implied,
                );
            }
        }

        if ($has_wedge) {
            $clauses[] = new Clause([], $conditional_object_id, $conditional_object_id, true);
        }

        return $clauses;
    }

    /**
     * Negates a set of clauses
     * negateClauses([$a || $b]) => !$a && !$b
     * negateClauses([$a, $b]) => !$a || !$b
     * negateClauses([$a, $b || $c]) =>
     *   (!$a || !$b) &&
     *   (!$a || !$c)
     * negateClauses([$a, $b || $c, $d || $e || $f]) =>
     *   (!$a || !$b || !$d) &&
     *   (!$a || !$b || !$e) &&
     *   (!$a || !$b || !$f) &&
     *   (!$a || !$c || !$d) &&
     *   (!$a || !$c || !$e) &&
     *   (!$a || !$c || !$f)
     *
     * Implications are kept as they are, and the formula is negated as two readings apart: with the results of its
     * calls, leaving out implied clauses, and with what the calls assert in place of their results, made of the
     * implied clauses and the others not about those results, whose negation is implied in turn.
     *
     * @param list<Clause>  $clauses
     * @return non-empty-list<Clause>
     */
    public static function negateFormula(array $clauses): array
    {
        $implications = [];
        $premise_var_ids = [];

        foreach ($clauses as $clause) {
            if ($clause->premise_var_id !== null) {
                $implications[] = $clause;
                $premise_var_ids[$clause->premise_var_id] = true;
            }
        }

        if ($implications) {
            $reading_with_results = [];
            $reading_with_assertions = [];

            foreach ($clauses as $clause) {
                if ($clause->premise_var_id !== null) {
                    continue;
                }

                if (!$clause->implied) {
                    $reading_with_results[] = $clause;
                }

                if ($clause->implied || !array_intersect_key($clause->possibilities, $premise_var_ids)) {
                    $reading_with_assertions[] = $clause;
                }
            }

            $negated_clauses = self::negateFormula($reading_with_results);

            if ($reading_with_assertions) {
                try {
                    foreach (self::negateFormula($reading_with_assertions) as $clause) {
                        if (!$clause->wedge) {
                            $negated_clauses[] = $clause->setImplied();
                        }
                    }
                } catch (ComplicatedExpressionException) {
                }
            }

            return [...$negated_clauses, ...$implications];
        }

        $reconcilable_clauses = array_filter(
            $clauses,
            static fn(Clause $clause): bool => $clause->reconcilable,
        );

        // A clause that can't be reconciled, as one of its disjuncts says nothing (`$a === null || !f($a)`, with
        // f() asserting nothing when false), still lists disjuncts that are all false where it is: where there is
        // nothing else to negate, that is what the negation says
        $clauses = $reconcilable_clauses ?: array_filter(
            $clauses,
            static fn(Clause $clause): bool => $clause->possibilities && !$clause->wedge,
        );

        if (!$clauses) {
            $cond_id = mt_rand(0, 100_000_000);
            return [new Clause([], $cond_id, $cond_id, true)];
        }

        $clauses_with_impossibilities = [];

        foreach ($clauses as $clause) {
            $clauses_with_impossibilities[] = $clause->calculateNegation();
        }

        unset($clauses);

        $impossible_clauses = self::groupImpossibilities($clauses_with_impossibilities);

        if (!$impossible_clauses) {
            $cond_id = mt_rand(0, 100_000_000);
            return [new Clause([], $cond_id, $cond_id, true)];
        }

        $negated = self::simplifyCNF($impossible_clauses);

        if (!$negated) {
            $cond_id = mt_rand(0, 100_000_000);
            return [new Clause([], $cond_id, $cond_id, true)];
        }

        return $negated;
    }
}
