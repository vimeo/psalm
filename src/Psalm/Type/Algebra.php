<?php
namespace Psalm\Type;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use PhpParser;
use Psalm\Codebase;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\FileSource;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Clause;
use function strlen;
use function strpos;
use function substr;

class Algebra
{
    /**
     * @param  array<string, non-empty-list<non-empty-list<string>>>  $all_types
     *
     * @return array<string, non-empty-list<non-empty-list<string>>>
     */
    public static function negateTypes(array $all_types)
    {
        return array_filter(
            array_map(
                /**
                 * @param  non-empty-list<non-empty-list<string>> $anded_types
                 *
                 * @return list<non-empty-list<string>>
                 */
                function (array $anded_types) {
                    if (count($anded_types) > 1) {
                        $new_anded_types = [];

                        foreach ($anded_types as $orred_types) {
                            if (count($orred_types) > 1) {
                                return [];
                            }

                            $new_anded_types[] = self::negateType($orred_types[0]);
                        }

                        return [$new_anded_types];
                    }

                    $new_orred_types = [];

                    foreach ($anded_types[0] as $orred_type) {
                        $new_orred_types[] = [self::negateType($orred_type)];
                    }

                    return $new_orred_types;
                },
                $all_types
            )
        );
    }

    /**
     * @param  string $type
     *
     * @return  string
     */
    private static function negateType($type)
    {
        if ($type === 'mixed') {
            return $type;
        }

        return $type[0] === '!' ? substr($type, 1) : '!' . $type;
    }

    /**
     * @param  PhpParser\Node\Expr      $conditional
     * @param  string|null              $this_class_name
     * @param  FileSource         $source
     *
     * @return array<int, Clause>
     */
    public static function getFormula(
        int $object_id,
        PhpParser\Node\Expr $conditional,
        $this_class_name,
        FileSource $source,
        Codebase $codebase = null,
        bool $inside_negation = false,
        bool $cache = true
    ) {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $left_assertions = self::getFormula(
                $object_id,
                $conditional->left,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            $right_assertions = self::getFormula(
                $object_id,
                $conditional->right,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            return array_merge(
                $left_assertions,
                $right_assertions
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            // at the moment we only support formulae in CNF

            $left_clauses = self::getFormula(
                $object_id,
                $conditional->left,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            $right_clauses = self::getFormula(
                $object_id,
                $conditional->right,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            return self::combineOredClauses($left_clauses, $right_clauses);
        }

        if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
                $and_expr = new PhpParser\Node\Expr\BinaryOp\BooleanAnd(
                    new PhpParser\Node\Expr\BooleanNot(
                        $conditional->expr->left,
                        $conditional->getAttributes()
                    ),
                    new PhpParser\Node\Expr\BooleanNot(
                        $conditional->expr->right,
                        $conditional->getAttributes()
                    ),
                    $conditional->expr->getAttributes()
                );

                return self::getFormula(
                    $object_id,
                    $and_expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    false
                );
            }

            if ($conditional->expr instanceof PhpParser\Node\Expr\Isset_
                && count($conditional->expr->vars) > 1
            ) {
                $assertions = null;

                if ($cache && $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                    $assertions = $source->node_data->getAssertions($conditional->expr);
                }

                if ($assertions === null) {
                    $assertions = AssertionFinder::scrapeAssertions(
                        $conditional->expr,
                        $this_class_name,
                        $source,
                        $codebase,
                        $inside_negation,
                        $cache
                    );

                    if ($cache && $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                        $source->node_data->setAssertions($conditional->expr, $assertions);
                    }
                }

                if ($assertions !== null) {
                    $clauses = [];

                    foreach ($assertions as $var => $anded_types) {
                        $redefined = false;

                        if ($var[0] === '=') {
                            /** @var string */
                            $var = substr($var, 1);
                            $redefined = true;
                        }

                        foreach ($anded_types as $orred_types) {
                            $clauses[] = new Clause(
                                [$var => $orred_types],
                                false,
                                true,
                                $orred_types[0][0] === '='
                                    || $orred_types[0][0] === '~'
                                    || (strlen($orred_types[0]) > 1
                                        && ($orred_types[0][1] === '='
                                            || $orred_types[0][1] === '~')),
                                $redefined ? [$var => true] : [],
                                $object_id
                            );
                        }
                    }

                    return self::negateFormula($clauses);
                }
            }

            if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
                $and_expr = new PhpParser\Node\Expr\BinaryOp\BooleanOr(
                    new PhpParser\Node\Expr\BooleanNot(
                        $conditional->expr->left,
                        $conditional->getAttributes()
                    ),
                    new PhpParser\Node\Expr\BooleanNot(
                        $conditional->expr->right,
                        $conditional->getAttributes()
                    ),
                    $conditional->expr->getAttributes()
                );

                return self::getFormula(
                    $object_id,
                    $and_expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    false
                );
            }
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
            $false_pos = AssertionFinder::hasFalseVariable($conditional);

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr)
            ) {
                $inside_negation = !$inside_negation;

                return self::getFormula(
                    $object_id,
                    $conditional->left,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            } elseif ($false_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr)
            ) {
                $inside_negation = !$inside_negation;

                return self::getFormula(
                    $object_id,
                    $conditional->right,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache
                );
            }
        }

        $assertions = null;

        if ($cache && $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            $assertions = $source->node_data->getAssertions($conditional);
        }

        if ($assertions === null) {
            $assertions = AssertionFinder::scrapeAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache
            );

            if ($cache && $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                $source->node_data->setAssertions($conditional, $assertions);
            }
        }

        if ($assertions) {
            $clauses = [];

            foreach ($assertions as $var => $anded_types) {
                $redefined = false;

                if ($var[0] === '=') {
                    /** @var string */
                    $var = substr($var, 1);
                    $redefined = true;
                }

                foreach ($anded_types as $orred_types) {
                    $clauses[] = new Clause(
                        [$var => $orred_types],
                        false,
                        true,
                        $orred_types[0][0] === '='
                            || $orred_types[0][0] === '~'
                            || (strlen($orred_types[0]) > 1
                                && ($orred_types[0][1] === '='
                                    || $orred_types[0][1] === '~')),
                        $redefined ? [$var => true] : [],
                        $object_id
                    );
                }
            }

            return $clauses;
        }

        return [new Clause([], true)];
    }

    /**
     * This is a very simple simplification heuristic
     * for CNF formulae.
     *
     * It simplifies formulae:
     *     ($a) && ($a || $b) => $a
     *     (!$a) && (!$b) && ($a || $b || $c) => $c
     *
     * @param  array<int, Clause>  $clauses
     *
     * @return list<Clause>
     */
    public static function simplifyCNF(array $clauses)
    {
        $cloned_clauses = [];

        // avoid strict duplicates
        foreach ($clauses as $clause) {
            $unique_clause = clone $clause;
            foreach ($unique_clause->possibilities as $var_id => $possibilities) {
                $unique_clause->possibilities[$var_id] = array_values(array_unique($possibilities));
            }
            $cloned_clauses[$clause->getHash()] = $unique_clause;
        }

        // remove impossible types
        foreach ($cloned_clauses as $clause_a) {
            if (count($clause_a->possibilities) !== 1 || count(array_values($clause_a->possibilities)[0]) !== 1) {
                continue;
            }

            if (!$clause_a->reconcilable || $clause_a->wedge) {
                continue;
            }

            $clause_var = array_keys($clause_a->possibilities)[0];
            $only_type = array_pop(array_values($clause_a->possibilities)[0]);
            $negated_clause_type = self::negateType($only_type);

            foreach ($cloned_clauses as $clause_b) {
                if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
                    continue;
                }

                if (isset($clause_b->possibilities[$clause_var]) &&
                    in_array($negated_clause_type, $clause_b->possibilities[$clause_var], true)
                ) {
                    $clause_var_possibilities = array_values(
                        array_filter(
                            $clause_b->possibilities[$clause_var],
                            /**
                             * @param string $possible_type
                             *
                             * @return bool
                             */
                            function ($possible_type) use ($negated_clause_type) {
                                return $possible_type !== $negated_clause_type;
                            }
                        )
                    );

                    if (!$clause_var_possibilities) {
                        unset($clause_b->possibilities[$clause_var]);
                        $clause_b->impossibilities = null;
                    } else {
                        $clause_b->possibilities[$clause_var] = $clause_var_possibilities;
                    }
                }
            }
        }

        $deduped_clauses = [];

        // avoid strict duplicates
        foreach ($cloned_clauses as $clause) {
            $deduped_clauses[$clause->getHash()] = clone $clause;
        }

        $deduped_clauses = array_filter(
            $deduped_clauses,
            /**
             * @return bool
             */
            function (Clause $clause) {
                return count($clause->possibilities) || $clause->wedge;
            }
        );

        $simplified_clauses = [];

        foreach ($deduped_clauses as $clause_a) {
            $is_redundant = false;

            foreach ($deduped_clauses as $clause_b) {
                if ($clause_a === $clause_b
                    || !$clause_b->reconcilable
                    || $clause_b->wedge
                    || $clause_a->wedge
                ) {
                    continue;
                }

                if ($clause_a->contains($clause_b)) {
                    $is_redundant = true;
                    break;
                }
            }

            if (!$is_redundant) {
                $simplified_clauses[] = $clause_a;
            }
        }

        return $simplified_clauses;
    }

    /**
     * Look for clauses with only one possible value
     *
     * @param  list<Clause>  $clauses
     * @param  array<string, bool> $cond_referenced_var_ids
     * @param  array<string, array<int, array<int, string>>> $active_truths
     *
     * @return array<string, array<int, array<int, string>>>
     */
    public static function getTruthsFromFormula(
        array $clauses,
        ?int $creating_object_id = null,
        array &$cond_referenced_var_ids = [],
        array &$active_truths = []
    ) {
        $truths = [];
        $active_truths = [];

        if (empty($clauses)) {
            return [];
        }

        foreach ($clauses as $clause) {
            if (!$clause->reconcilable) {
                continue;
            }

            foreach ($clause->possibilities as $var => $possible_types) {
                // if there's only one possible type, return it
                if (count($clause->possibilities) === 1 && count($possible_types) === 1) {
                    $possible_type = array_pop($possible_types);

                    if (isset($truths[$var]) && !isset($clause->redefined_vars[$var])) {
                        $truths[$var][] = [$possible_type];
                    } else {
                        $truths[$var] = [[$possible_type]];
                    }

                    if ($creating_object_id && $creating_object_id === $clause->creating_object_id) {
                        if (!isset($active_truths[$var])) {
                            $active_truths[$var] = [];
                        }

                        $active_truths[$var][count($truths[$var]) - 1] = [$possible_type];
                    }
                } elseif (count($clause->possibilities) === 1) {
                    // if there's only one active clause, return all the non-negation clause members ORed together
                    $things_that_can_be_said = array_filter(
                        $possible_types,
                        /**
                         * @param  string $possible_type
                         *
                         * @return bool
                         */
                        function ($possible_type) {
                            return $possible_type[0] !== '!';
                        }
                    );

                    if ($things_that_can_be_said && count($things_that_can_be_said) === count($possible_types)) {
                        $things_that_can_be_said = array_unique($things_that_can_be_said);

                        if ($clause->generated && count($possible_types) > 1) {
                            unset($cond_referenced_var_ids[$var]);
                        }

                        /** @var array<int, string> $things_that_can_be_said */
                        $truths[$var] = [$things_that_can_be_said];

                        if ($creating_object_id && $creating_object_id === $clause->creating_object_id) {
                            $active_truths[$var] = [$things_that_can_be_said];
                        }
                    }
                }
            }
        }

        return $truths;
    }

    /**
     * @param  non-empty-array<int, Clause>  $clauses
     *
     * @return array<int, Clause>
     */
    public static function groupImpossibilities(array $clauses)
    {
        $complexity = 1;

        $seed_clauses = [];

        $clause = array_pop($clauses);

        if (!$clause->wedge) {
            if ($clause->impossibilities === null) {
                throw new \UnexpectedValueException('$clause->impossibilities should not be null');
            }

            foreach ($clause->impossibilities as $var => $impossible_types) {
                foreach ($impossible_types as $impossible_type) {
                    $seed_clause = new Clause(
                        [$var => [$impossible_type]],
                        false,
                        true,
                        false,
                        [],
                        $clause->creating_object_id
                    );

                    $seed_clauses[] = $seed_clause;

                    ++$complexity;
                }
            }
        }

        if (!$clauses || !$seed_clauses) {
            return $seed_clauses;
        }

        while ($clauses) {
            $clause = array_pop($clauses);

            $new_clauses = [];

            foreach ($seed_clauses as $grouped_clause) {
                if ($clause->impossibilities === null) {
                    throw new \UnexpectedValueException('$clause->impossibilities should not be null');
                }

                foreach ($clause->impossibilities as $var => $impossible_types) {
                    foreach ($impossible_types as $impossible_type) {
                        $new_clause_possibilities = $grouped_clause->possibilities;

                        if (isset($grouped_clause->possibilities[$var])) {
                            $new_clause_possibilities[$var][] = $impossible_type;
                        } else {
                            $new_clause_possibilities[$var] = [$impossible_type];
                        }

                        $new_clause = new Clause(
                            $new_clause_possibilities,
                            false,
                            true,
                            true,
                            [],
                            $clause->creating_object_id === $grouped_clause->creating_object_id
                                ? $clause->creating_object_id
                                : null
                        );

                        $new_clauses[] = $new_clause;

                        ++$complexity;

                        if ($complexity > 20000) {
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
     * @param  array<int, Clause>  $left_clauses
     * @param  array<int, Clause>  $right_clauses
     *
     * @return array<int, Clause>
     */
    public static function combineOredClauses(array $left_clauses, array $right_clauses)
    {
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
            return [new Clause([], true)];
        }

        foreach ($left_clauses as $left_clause) {
            foreach ($right_clauses as $right_clause) {
                if ($left_clause->wedge && $right_clause->wedge) {
                    // handled below
                    continue;
                }

                /** @var  array<string, non-empty-list<string>> */
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
                    if (isset($right_clause->redefined_vars[$var])) {
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

                if (count($left_clauses) > 1 || count($right_clauses) > 1) {
                    foreach ($possibilities as $var => $p) {
                        $possibilities[$var] = array_values(array_unique($p));
                    }
                }

                foreach ($possibilities as $var_possibilities) {
                    if (count($var_possibilities) === 2) {
                        if ($var_possibilities[0] === '!' . $var_possibilities[1]
                            || $var_possibilities[1] === '!' . $var_possibilities[0]
                        ) {
                            continue 2;
                        }
                    }
                }

                $creating_object_id = $right_clause->creating_object_id === $left_clause->creating_object_id
                    ? $right_clause->creating_object_id
                    : null;

                $clauses[] = new Clause(
                    $possibilities,
                    false,
                    $can_reconcile,
                    $right_clause->generated
                        || $left_clause->generated
                        || count($left_clauses) > 1
                        || count($right_clauses) > 1,
                    [],
                    $creating_object_id
                );
            }
        }

        if ($has_wedge) {
            $clauses[] = new Clause([], true);
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
     * @param  array<int, Clause>  $clauses
     *
     * @return non-empty-list<Clause>
     */
    public static function negateFormula(array $clauses)
    {
        if (!$clauses) {
            return [new Clause([], true)];
        }

        foreach ($clauses as $clause) {
            self::calculateNegation($clause);
        }

        $impossible_clauses = self::groupImpossibilities($clauses);

        if (!$impossible_clauses) {
            return [new Clause([], true)];
        }

        $negated = self::simplifyCNF($impossible_clauses);

        if (!$negated) {
            return [new Clause([], true)];
        }

        return $negated;
    }

    /**
     * @param  Clause $clause
     *
     * @return void
     */
    public static function calculateNegation(Clause $clause)
    {
        if ($clause->impossibilities !== null) {
            return;
        }

        $impossibilities = [];

        foreach ($clause->possibilities as $var_id => $possibility) {
            $impossibility = [];

            foreach ($possibility as $type) {
                if (($type[0] !== '=' && $type[0] !== '~'
                        && (!isset($type[1]) || ($type[1] !== '=' && $type[1] !== '~')))
                    || strpos($type, '(')
                    || strpos($type, 'getclass-')
                ) {
                    $impossibility[] = self::negateType($type);
                }
            }

            if ($impossibility) {
                $impossibilities[$var_id] = $impossibility;
            }
        }

        $clause->impossibilities = $impossibilities;
    }
}
