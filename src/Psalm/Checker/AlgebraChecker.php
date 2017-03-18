<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Checker\Statements\Expression\AssertionFinder;
use Psalm\Clause;
use Psalm\StatementsSource;

class AlgebraChecker
{
    /**
     * @param  PhpParser\Node\Expr      $conditional
     * @param  string|null              $this_class_name
     * @param  StatementsSource         $source
     * @return array<int, Clause>
     */
    public static function getFormula(
        PhpParser\Node\Expr $conditional,
        $this_class_name,
        StatementsSource $source
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

            if (!$conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd &&
                !$conditional->left instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
            ) {
                $left_clauses = self::getFormula(
                    $conditional->left,
                    $this_class_name,
                    $source
                );
            } else {
                $left_clauses = [new Clause([], true)];
            }

            if (!$conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd &&
                !$conditional->right instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
            ) {
                $right_clauses = self::getFormula(
                    $conditional->right,
                    $this_class_name,
                    $source
                );
            } else {
                $right_clauses = [new Clause([], true)];
            }

            /** @var array<string, array<string>> */
            $possibilities = [];

            if ($left_clauses[0]->wedge && $right_clauses[0]->wedge) {
                return [new Clause([], true)];
            }

            $can_reconcile = true;

            if ($left_clauses[0]->wedge ||
                $right_clauses[0]->wedge ||
                !$left_clauses[0]->reconcilable ||
                !$right_clauses[0]->reconcilable
            ) {
                $can_reconcile = false;
            }

            foreach ($left_clauses[0]->possibilities as $var => $possible_types) {
                if (isset($possibilities[$var])) {
                    $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
                } else {
                    $possibilities[$var] = $possible_types;
                }
            }

            foreach ($right_clauses[0]->possibilities as $var => $possible_types) {
                if (isset($possibilities[$var])) {
                    $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
                } else {
                    $possibilities[$var] = $possible_types;
                }
            }

            return [new Clause($possibilities, false, $can_reconcile)];
        }

        $assertions = AssertionFinder::getAssertions(
            $conditional,
            $this_class_name,
            $source
        );

        if ($assertions) {
            $clauses = [];

            foreach ($assertions as $var => $type) {
                if ($type === 'isset') {
                    $key_parts = preg_split(
                        '/(\]|\[)/',
                        $var,
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );

                    $base = array_shift($key_parts);

                    $clauses[] = new Clause([$base => ['isset']]);

                    if (count($key_parts)) {
                        $clauses[] = new Clause([$base => ['!false']]);
                        $clauses[] = new Clause([$base => ['!int']]);
                    }

                    foreach ($key_parts as $i => $key_part_dim) {
                        $base .= '[' . $key_part_dim . ']';
                        $clauses[] = new Clause([$base => ['isset']]);

                        if ($i < count($key_parts) - 1) {
                            $clauses[] = new Clause([$base => ['!false']]);
                            $clauses[] = new Clause([$base => ['!int']]);
                        }
                    }
                } else {
                    $clauses[] = new Clause([$var => [$type]]);
                }
            }

            return $clauses;
        }

        return [new Clause([], true)];
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
     * @param  array<Clause>  $clauses
     * @return array<Clause>
     */
    public static function negateFormula(array $clauses)
    {
        foreach ($clauses as $clause) {
            self::calculateNegation($clause);
        }

        return self::groupImpossibilities($clauses);
    }

    /**
     * @param  Clause $clause
     * @return void
     */
    public static function calculateNegation(Clause $clause)
    {
        if ($clause->impossibilities !== null) {
            return;
        }

        $clause->impossibilities = array_map(
            /**
             * @param array<string> $types
             * @return array<string>
             */
            function (array $types) {
                return array_map(
                    /**
                     * @param string $type
                     * @return string
                     */
                    function ($type) {
                        return TypeChecker::negateType($type);
                    },
                    $types
                );
            },
            $clause->possibilities
        );
    }

    /**
     * This is a very simple simplification heuristic
     * for CNF formulae.
     *
     * It simplifies formulae:
     *     ($a) && ($a || $b) => $a
     *     (!$a) && (!$b) && ($a || $b || $c) => $c
     *
     * @param  array<Clause>  $clauses
     * @return array<Clause>
     */
    public static function simplifyCNF(array $clauses)
    {
        $cloned_clauses = [];

        // avoid strict duplicates
        foreach ($clauses as $clause) {
            $cloned_clauses[$clause->getHash()] = clone $clause;
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
            $negated_clause_type = TypeChecker::negateType($only_type);

            foreach ($cloned_clauses as $clause_b) {
                if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
                    continue;
                }

                if (isset($clause_b->possibilities[$clause_var]) &&
                    in_array($negated_clause_type, $clause_b->possibilities[$clause_var])
                ) {
                    $clause_b->possibilities[$clause_var] = array_filter(
                        $clause_b->possibilities[$clause_var],
                        /**
                         * @param string $possible_type
                         * @return bool
                         */
                        function ($possible_type) use ($negated_clause_type) {
                            return $possible_type !== $negated_clause_type;
                        }
                    );

                    if (count($clause_b->possibilities[$clause_var]) === 0) {
                        unset($clause_b->possibilities[$clause_var]);
                    }
                }
            }
        }

        $cloned_clauses = array_filter(
            $cloned_clauses,
            /**
             * @return bool
             */
            function (Clause $clause) {
                return (bool)count($clause->possibilities);
            }
        );

        $simplified_clauses = [];

        foreach ($cloned_clauses as $clause_a) {
            $is_redundant = false;

            foreach ($cloned_clauses as $clause_b) {
                if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
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
     * @param  array<Clause>  $clauses
     * @return array<string, string>
     */
    public static function getTruthsFromFormula(array $clauses)
    {
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
                        $truths[$var] .= '&' . array_pop($possible_types);
                    } else {
                        $truths[$var] = array_pop($possible_types);
                    }
                } elseif (count($clause->possibilities) === 1) {
                    // if there's only one active clause, return all the non-negation clause members ORed together
                    $things_that_can_be_said = array_filter(
                        $possible_types,
                        /**
                         * @param  string $possible_type
                         * @return bool
                         */
                        function ($possible_type) {
                            return $possible_type[0] !== '!';
                        }
                    );

                    if ($things_that_can_be_said && count($things_that_can_be_said) === count($possible_types)) {
                        $things_that_can_be_said = array_unique($things_that_can_be_said);
                        $truths[$var] = implode('|', array_unique($things_that_can_be_said));
                    }
                }
            }
        }

        return $truths;
    }

    /**
     * @param  array<Clause>  $clauses
     * @return array<Clause>
     */
    protected static function groupImpossibilities(array $clauses)
    {
        $clause = array_pop($clauses);

        $new_clauses = [];

        if (count($clauses)) {
            $grouped_clauses = self::groupImpossibilities($clauses);

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

                        $new_clauses[] = new Clause($new_clause_possibilities);
                    }
                }
            }
        } elseif ($clause && !$clause->wedge) {
            if ($clause->impossibilities === null) {
                throw new \UnexpectedValueException('$clause->impossibilities should not be null');
            }

            foreach ($clause->impossibilities as $var => $impossible_types) {
                foreach ($impossible_types as $impossible_type) {
                    $new_clauses[] = new Clause([$var => [$impossible_type]]);
                }
            }
        }

        return $new_clauses;
    }
}
