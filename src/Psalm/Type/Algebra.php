<?php
namespace Psalm\Type;

use PhpParser;
use Psalm\Checker\Statements\Expression\AssertionFinder;
use Psalm\Clause;
use Psalm\CodeLocation;
use Psalm\FileSource;
use Psalm\IssueBuffer;
use Psalm\Type\Algebra;

class Algebra
{
    /**
     * @param  array<string, array<int, array<int, string>>>  $all_types
     *
     * @return array<string, array<int, array<int, string>>>
     */
    public static function negateTypes(array $all_types)
    {
        return array_map(
            /**
             * @param  array<int, array<int, string>> $anded_types
             *
             * @return  array<int, array<int, string>>
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
        PhpParser\Node\Expr $conditional,
        $this_class_name,
        FileSource $source
    ) {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $left_assertions = self::getFormula(
                $conditional->left,
                $this_class_name,
                $source
            );

            $right_assertions = self::getFormula(
                $conditional->right,
                $this_class_name,
                $source
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
                $conditional->left,
                $this_class_name,
                $source
            );

            $right_clauses = self::getFormula(
                $conditional->right,
                $this_class_name,
                $source
            );

            return self::combineOredClauses($left_clauses, $right_clauses);
        }

        $assertions = AssertionFinder::getAssertions(
            $conditional,
            $this_class_name,
            $source
        );

        if ($assertions) {
            $clauses = [];

            foreach ($assertions as $var => $anded_types) {
                foreach ($anded_types as $orred_types) {
                    $clauses[] = new Clause(
                        [$var => $orred_types],
                        false,
                        true,
                        $orred_types[0][0] === '^'
                            || $orred_types[0][0] === '~'
                            || (strlen($orred_types[0][0]) > 1
                                && ($orred_types[0][0][1] === '^'
                                    || $orred_types[0][0][1] === '~'))
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
     * @return array<int, Clause>
     */
    public static function simplifyCNF(array $clauses)
    {
        $cloned_clauses = [];

        // avoid strict duplicates
        foreach ($clauses as $clause) {
            $unique_clause = clone $clause;
            foreach ($unique_clause->possibilities as $var_id => $possibilities) {
                if (count($possibilities)) {
                    $unique_clause->possibilities[$var_id] = array_unique($possibilities);
                }
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
                    $clause_b->possibilities[$clause_var] = array_filter(
                        $clause_b->possibilities[$clause_var],
                        /**
                         * @param string $possible_type
                         *
                         * @return bool
                         */
                        function ($possible_type) use ($negated_clause_type) {
                            return $possible_type !== $negated_clause_type;
                        }
                    );

                    if (count($clause_b->possibilities[$clause_var]) === 0) {
                        unset($clause_b->possibilities[$clause_var]);
                        $clause_b->impossibilities = null;
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
     * @param  array<int, Clause>  $clauses
     * @param  array<string, bool> $cond_referenced_var_ids
     *
     * @return array<string, array<int, array<int, string>>>
     */
    public static function getTruthsFromFormula(
        array $clauses,
        array &$cond_referenced_var_ids = []
    ) {
        $truths = [];

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
                    if (isset($truths[$var])) {
                        $truths[$var][] = [array_pop($possible_types)];
                    } else {
                        $truths[$var] = [[array_pop($possible_types)]];
                    }
                } elseif (count($clause->possibilities) === 1) {
                    // if there's only one active clause, return all the non-negation clause members ORed together
                    $things_that_can_be_said = array_filter(
                        $possible_types,
                        /**
                         * @param  string $possible_type
                         *
                         * @return bool
                         *
                         * @psalm-suppress MixedOperand
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
                    }
                }
            }
        }

        return $truths;
    }

    /**
     * @param  array<int, Clause>  $clauses
     *
     * @return array<int, Clause>
     */
    private static function groupImpossibilities(array $clauses)
    {
        if (count($clauses) > 5000) {
            return [];
        }

        $clause = array_shift($clauses);

        $new_clauses = [];

        if ($clauses) {
            $grouped_clauses = self::groupImpossibilities($clauses);

            if (count($grouped_clauses) > 5000) {
                return [];
            }

            foreach ($grouped_clauses as $grouped_clause) {
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

                        $new_clause = new Clause($new_clause_possibilities, false, true, true);

                        $new_clauses[] = $new_clause;
                    }
                }
            }
        } elseif ($clause && !$clause->wedge) {
            if ($clause->impossibilities === null) {
                throw new \UnexpectedValueException('$clause->impossibilities should not be null');
            }

            foreach ($clause->impossibilities as $var => $impossible_types) {
                foreach ($impossible_types as $impossible_type) {
                    $new_clause = new Clause([$var => [$impossible_type]]);

                    $new_clauses[] = $new_clause;
                }
            }
        }

        return $new_clauses;
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
                        $possibilities[$var] = array_unique($p);
                    }
                }

                $clauses[] = new Clause(
                    $possibilities,
                    false,
                    $can_reconcile,
                    $right_clause->generated
                        || $left_clause->generated
                        || count($left_clauses) > 1
                        || count($right_clauses) > 1
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
     * @return array<int, Clause>
     */
    public static function negateFormula(array $clauses)
    {
        foreach ($clauses as $clause) {
            self::calculateNegation($clause);
        }

        $negated = self::simplifyCNF(self::groupImpossibilities($clauses));
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

        foreach ($clause->possibilities as $var_id => $possiblity) {
            $impossibility = [];

            foreach ($possiblity as $type) {
                if (($type[0] !== '^' && $type[0] !== '~'
                        && (!isset($type[1]) || ($type[1] !== '^' && $type[1] !== '~')))
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
