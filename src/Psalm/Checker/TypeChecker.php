<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Clause;
use Psalm\CodeLocation;
use Psalm\Issue\FailedTypeResolution;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;

class TypeChecker
{
    const ASSIGNMENT_TO_RIGHT = 1;
    const ASSIGNMENT_TO_LEFT = -1;

    /**
     * @param  PhpParser\Node\Expr      $conditional
     * @param  string                   $this_class_name
     * @param  string                   $namespace
     * @param  array<string, string>    $aliased_classes
     * @return array<int, Clause>
     */
    public static function getFormula(
        PhpParser\Node\Expr $conditional,
        $this_class_name,
        $namespace,
        array $aliased_classes
    ) {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            $left_assertions = self::getFormula(
                $conditional->left,
                $this_class_name,
                $namespace,
                $aliased_classes
            );

            $right_assertions = self::getFormula(
                $conditional->right,
                $this_class_name,
                $namespace,
                $aliased_classes
            );

            return array_merge(
                $left_assertions,
                $right_assertions
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            // at the moment we only support formulae in CNF

            if (!$conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
                $left_clauses = self::getFormula(
                    $conditional->left,
                    $this_class_name,
                    $namespace,
                    $aliased_classes
                );
            } else {
                $left_clauses = [new Clause([], true)];
            }

            if (!$conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
                $right_clauses = self::getFormula(
                    $conditional->right,
                    $this_class_name,
                    $namespace,
                    $aliased_classes
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

        $assertions = self::getTypeAssertions(
            $conditional,
            $this_class_name,
            $namespace,
            $aliased_classes
        );

        if ($assertions) {
            $possibilities = [];

            foreach ($assertions as $var => $type) {
                $possibilities[$var] = [$type];
            }

            return [new Clause($possibilities)];
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
                        return self::negateType($type);
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
            $negated_clause_type = self::negateType($only_type);

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
                if ($clause_a === $clause_b) {
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
                    $things_that_can_be_said = implode(
                        '|',
                        array_filter(
                            $possible_types,
                            /**
                             * @param  string $possible_type
                             * @return bool
                             */
                            function ($possible_type) {
                                return $possible_type[0] !== '!';
                            }
                        )
                    );

                    if ($things_that_can_be_said) {
                        $truths[$var] = $things_that_can_be_said;
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

    /**
     * @param   array<string, string>   $left_assertions
     * @param   array<string, string>   $right_assertions
     * @return  array
     */
    private static function combineTypeAssertions(array $left_assertions, array $right_assertions)
    {
        $keys = array_merge(array_keys($left_assertions), array_keys($right_assertions));
        $keys = array_unique($keys);

        $if_types = [];

        foreach ($keys as $key) {
            if (isset($left_assertions[$key]) && isset($right_assertions[$key])) {
                if ($left_assertions[$key][0] !== '!' && $right_assertions[$key][0] !== '!') {
                    $if_types[$key] = $left_assertions[$key] . '&' . $right_assertions[$key];
                } else {
                    $if_types[$key] = $right_assertions[$key];
                }
            } elseif (isset($left_assertions[$key])) {
                $if_types[$key] = $left_assertions[$key];
            } else {
                $if_types[$key] = $right_assertions[$key];
            }
        }

        return $if_types;
    }

    /**
     * Gets all the type assertions in a conditional
     *
     * @param  PhpParser\Node\Expr      $conditional
     * @param  string                   $this_class_name
     * @param  string                   $namespace
     * @param  array<string, string>    $aliased_classes
     * @param  bool                 $allow_non_negatable Allow type assertions that should not be negated
     * @return array<string, string>
     */
    public static function getTypeAssertions(
        PhpParser\Node\Expr $conditional,
        $this_class_name,
        $namespace,
        array $aliased_classes,
        $allow_non_negatable = false
    ) {
        $if_types = [];

        if ($conditional instanceof PhpParser\Node\Expr\Instanceof_) {
            $instanceof_type = self::getInstanceOfTypes($conditional, $this_class_name, $namespace, $aliased_classes);

            if ($instanceof_type) {
                $var_name = ExpressionChecker::getArrayVarId(
                    $conditional->expr,
                    $this_class_name,
                    $namespace,
                    $aliased_classes
                );

                if ($var_name) {
                    $if_types[$var_name] = $instanceof_type;
                }
            }
        } elseif ($var_name = ExpressionChecker::getArrayVarId(
            $conditional,
            $this_class_name,
            $namespace,
            $aliased_classes
        )) {
            $if_types[$var_name] = '!empty';
        } elseif ($conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->var,
                $this_class_name,
                $namespace,
                $aliased_classes
            );

            if ($var_name) {
                $if_types[$var_name] = '!empty';
            }
        } elseif ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            if ($conditional->expr instanceof PhpParser\Node\Expr\Instanceof_) {
                $instanceof_type = self::getInstanceOfTypes(
                    $conditional->expr,
                    $this_class_name,
                    $namespace,
                    $aliased_classes
                );

                if ($instanceof_type) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->expr->expr,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    if ($var_name) {
                        $if_types[$var_name] = '!' . $instanceof_type;
                    }
                }
            } elseif ($var_name = ExpressionChecker::getArrayVarId(
                $conditional->expr,
                $this_class_name,
                $namespace,
                $aliased_classes
            )) {
                $if_types[$var_name] = 'empty';
            } elseif ($conditional->expr instanceof PhpParser\Node\Expr\Assign) {
                $var_name = ExpressionChecker::getArrayVarId(
                    $conditional->expr->var,
                    $this_class_name,
                    $namespace,
                    $aliased_classes
                );

                if ($var_name) {
                    $if_types[$var_name] = 'empty';
                }
            } elseif ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Identical ||
                $conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Equal
            ) {
                $null_position = self::hasNullVariable($conditional->expr);
                $false_position = self::hasFalseVariable($conditional->expr);

                if ($null_position !== null) {
                    if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->expr->left,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->expr->right,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    } else {
                        throw new \InvalidArgumentException('Bad null variable position');
                    }

                    if ($var_name) {
                        if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                            $if_types[$var_name] = '!null';
                        } else {
                            // we do this because == null gives us a weaker idea than === null
                            $if_types[$var_name] = '!empty';
                        }
                    }
                } elseif ($false_position !== null) {
                    if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->expr->left,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->expr->right,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    } else {
                        throw new \InvalidArgumentException('Bad null variable position');
                    }

                    if ($var_name) {
                        if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                            $if_types[$var_name] = '!false';
                        } else {
                            // we do this because == null gives us a weaker idea than === null
                            $if_types[$var_name] = '!empty';
                        }
                    }
                }
            } elseif ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical ||
                $conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
            ) {
                $null_position = self::hasNullVariable($conditional->expr);
                $false_position = self::hasFalseVariable($conditional->expr);

                if ($null_position !== null) {
                    if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->expr->left,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->expr->right,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    } else {
                        throw new \InvalidArgumentException('Bad null variable position');
                    }

                    if ($var_name) {
                        if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                            $if_types[$var_name] = 'null';
                        } else {
                            $if_types[$var_name] = 'empty';
                        }
                    }
                } elseif ($false_position !== null) {
                    if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->expr->left,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->expr->right,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    } else {
                        throw new \InvalidArgumentException('Bad null variable position');
                    }

                    if ($var_name) {
                        if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                            $if_types[$var_name] = 'false';
                        } else {
                            // we do this because == null gives us a weaker idea than === null
                            $if_types[$var_name] = 'empty';
                        }
                    }
                }
            } elseif ($conditional->expr instanceof PhpParser\Node\Expr\Empty_) {
                $var_name = ExpressionChecker::getArrayVarId(
                    $conditional->expr->expr,
                    $this_class_name,
                    $namespace,
                    $aliased_classes
                );

                if ($var_name) {
                    $if_types[$var_name] = '!empty';
                }
            } elseif ($conditional->expr instanceof PhpParser\Node\Expr\FuncCall) {
                self::processFunctionCall(
                    $conditional->expr,
                    $if_types,
                    $this_class_name,
                    $namespace,
                    $aliased_classes,
                    true
                );
            } elseif ($conditional->expr instanceof PhpParser\Node\Expr\Isset_) {
                foreach ($conditional->expr->vars as $isset_var) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $isset_var,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    if ($var_name) {
                        $if_types[$var_name] = 'isset';
                    }
                }
            }
        } elseif ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
        ) {
            $null_position = self::hasNullVariable($conditional);
            $false_position = self::hasFalseVariable($conditional);
            $gettype_position = self::hasGetTypeCheck($conditional);
            $scalar_value_position = $allow_non_negatable ? self::hasScalarValueComparison($conditional) : false;

            $var_name = null;

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'null';
                    } else {
                        $if_types[$var_name] = 'empty';
                    }
                }
            } elseif ($false_position) {
                if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                    if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->left,
                            $if_types,
                            $this_class_name,
                            $namespace,
                            $aliased_classes,
                            true
                        );
                    } else {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->left,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    }
                } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->right,
                            $if_types,
                            $this_class_name,
                            $namespace,
                            $aliased_classes,
                            true
                        );
                    } else {
                        $var_name = ExpressionChecker::getArrayVarId(
                            $conditional->right,
                            $this_class_name,
                            $namespace,
                            $aliased_classes
                        );
                    }
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'false';
                    } else {
                        $if_types[$var_name] = 'empty';
                    }
                }
            } elseif ($gettype_position) {
                $var_type = null;

                if ($gettype_position === self::ASSIGNMENT_TO_RIGHT) {
                    /** @var PhpParser\Node\Expr\FuncCall $conditional->right */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right->args[0]->value,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    /** @var PhpParser\Node\Scalar\String_ $conditional->left */
                    $var_type = $conditional->left->value;
                } elseif ($gettype_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr\FuncCall $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left->args[0]->value,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    /** @var PhpParser\Node\Scalar\String_ $conditional->right */
                    $var_type = $conditional->right->value;
                }

                if ($var_name && $var_type) {
                    $if_types[$var_name] = $var_type;
                }
            } elseif ($scalar_value_position) {
                $var_type = null;

                if ($scalar_value_position === self::ASSIGNMENT_TO_RIGHT) {
                    /** @var PhpParser\Node\Expr $conditional->right */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    if ($conditional->right instanceof PhpParser\Node\Scalar\String_) {
                        $var_type = 'string';
                    } elseif ($conditional->right instanceof PhpParser\Node\Scalar\LNumber) {
                        $var_type = 'int';
                    } elseif ($conditional->right instanceof PhpParser\Node\Scalar\DNumber) {
                        $var_type = 'float';
                    }
                } elseif ($scalar_value_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );

                    if ($conditional->left instanceof PhpParser\Node\Scalar\String_) {
                        $var_type = 'string';
                    } elseif ($conditional->left instanceof PhpParser\Node\Scalar\LNumber) {
                        $var_type = 'int';
                    } elseif ($conditional->left instanceof PhpParser\Node\Scalar\DNumber) {
                        $var_type = 'float';
                    }
                }

                if ($var_name && $var_type) {
                    $if_types[$var_name] = $var_type;
                }
            }
        } elseif ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            $null_position = self::hasNullVariable($conditional);
            $false_position = self::hasFalseVariable($conditional);
            $true_position = self::hasTrueVariable($conditional);

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                        $if_types[$var_name] = '!null';
                    } else {
                        $if_types[$var_name] = '!empty';
                    }
                }
            } elseif ($false_position) {
                if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $namespace,
                        $aliased_classes
                    );
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                        $if_types[$var_name] = '!false';
                    } else {
                        $if_types[$var_name] = '!empty';
                    }
                }
            } elseif ($true_position) {
                if ($true_position === self::ASSIGNMENT_TO_RIGHT) {
                    if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->left,
                            $if_types,
                            $this_class_name,
                            $namespace,
                            $aliased_classes,
                            true
                        );
                    }
                } elseif ($true_position === self::ASSIGNMENT_TO_LEFT) {
                    if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall) {
                        self::processFunctionCall(
                            $conditional->right,
                            $if_types,
                            $this_class_name,
                            $namespace,
                            $aliased_classes,
                            true
                        );
                    }
                } else {
                    throw new \InvalidArgumentException('Bad null variable position');
                }
            }
        } elseif ($conditional instanceof PhpParser\Node\Expr\FuncCall) {
            self::processFunctionCall($conditional, $if_types, $this_class_name, $namespace, $aliased_classes, false);
        } elseif ($conditional instanceof PhpParser\Node\Expr\Empty_) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->expr,
                $this_class_name,
                $namespace,
                $aliased_classes
            );

            if ($var_name) {
                $if_types[$var_name] = 'empty';
            }
        } elseif ($conditional instanceof PhpParser\Node\Expr\Isset_) {
            foreach ($conditional->vars as $isset_var) {
                $var_name = ExpressionChecker::getArrayVarId(
                    $isset_var,
                    $this_class_name,
                    $namespace,
                    $aliased_classes
                );

                if ($var_name) {
                    $if_types[$var_name] = '!isset';
                }
            }
        }

        return $if_types;
    }

    /**
     * @param  PhpParser\Node\Expr\FuncCall $expr
     * @param  array<string>                &$if_types
     * @param  string                       $this_class_name
     * @param  string                       $namespace
     * @param  array<string, string>        $aliased_classes
     * @param  boolean                      $negate
     * @return void
     */
    protected static function processFunctionCall(
        PhpParser\Node\Expr\FuncCall $expr,
        array &$if_types,
        $this_class_name,
        $namespace,
        array $aliased_classes,
        $negate = false
    ) {
        $prefix = $negate ? '!' : '';

        $first_var_name = isset($expr->args[0]->value)
            ? ExpressionChecker::getArrayVarId(
                $expr->args[0]->value,
                $this_class_name,
                $namespace,
                $aliased_classes
            )
            : null;

        if (self::hasNullCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'null';
            }
        } elseif (self::hasIsACheck($expr)) {
            if ($first_var_name) {
                /** @var PhpParser\Node\Scalar\String_ */
                $is_a_type = $expr->args[1]->value;
                $if_types[$first_var_name] = $prefix . $is_a_type->value;
            }
        } elseif (self::hasArrayCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'array';
            }
        } elseif (self::hasBoolCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'bool';
            }
        } elseif (self::hasStringCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'string';
            }
        } elseif (self::hasObjectCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'object';
            }
        } elseif (self::hasNumericCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'numeric';
            }
        } elseif (self::hasIntCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'int';
            }
        } elseif (self::hasFloatCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'float';
            }
        } elseif (self::hasResourceCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'resource';
            }
        } elseif (self::hasScalarCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'scalar';
            }
        } elseif (self::hasCallableCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'callable';
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\Instanceof_ $stmt
     * @param  string                          $this_class_name
     * @param  string                          $namespace
     * @param  array<string, string>           $aliased_classes
     * @return string|null
     */
    protected static function getInstanceOfTypes(
        PhpParser\Node\Expr\Instanceof_ $stmt,
        $this_class_name,
        $namespace,
        array $aliased_classes
    ) {
        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                $instanceof_class = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $namespace,
                    $aliased_classes
                );

                return $instanceof_class;
            } elseif ($stmt->class->parts === ['self']) {
                return $this_class_name;
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  int|null
     */
    protected static function hasNullVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'null') {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'null') {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  int|null
     */
    protected static function hasFalseVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'false') {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'false') {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  int|null
     */
    protected static function hasTrueVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'true') {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'true') {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  false|int
     */
    protected static function hasGetTypeCheck(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'gettype' &&
            $conditional->left instanceof PhpParser\Node\Scalar\String_) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'gettype' &&
            $conditional->right instanceof PhpParser\Node\Scalar\String_) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     * @return  false|int
     */
    protected static function hasScalarValueComparison(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if (!$conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
            return false;
        }

        if ($conditional->right instanceof PhpParser\Node\Scalar) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Scalar) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasNullCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_null') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasIsACheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_a' &&
            $stmt->args[1]->value instanceof PhpParser\Node\Scalar\String_) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasArrayCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_array') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasStringCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_string') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasBoolCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_bool') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasObjectCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_object']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasNumericCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_numeric']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasIntCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['is_int'] ||
                $stmt->name->parts === ['is_integer']||
                $stmt->name->parts === ['is_long'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasFloatCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['is_float'] ||
                $stmt->name->parts === ['is_real'] ||
                $stmt->name->parts === ['is_double'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasResourceCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_resource']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasScalarCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_scalar']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @return  bool
     */
    protected static function hasCallableCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_callable']) {
            return true;
        }

        return false;
    }

    /**
     * Takes two arrays and consolidates them, removing null values from existing types where applicable
     *
     * @param  array<string, string>     $new_types
     * @param  array<string, Type\Union> $existing_types
     * @param  CodeLocation              $code_location
     * @param  array<string>             $suppressed_issues
     * @return array<string, Type\Union>|false
     */
    public static function reconcileKeyedTypes(
        array $new_types,
        array $existing_types,
        CodeLocation $code_location,
        array $suppressed_issues = []
    ) {
        $keys = [];

        foreach ($existing_types as $ek => $_) {
            if (!in_array($ek, $keys)) {
                $keys[] = $ek;
            }
        }

        foreach ($new_types as $nk => $_) {
            if (!in_array($nk, $keys)) {
                $keys[] = $nk;
            }
        }

        $result_types = [];

        if (empty($new_types)) {
            return $existing_types;
        }

        foreach ($keys as $key) {
            if (!isset($new_types[$key])) {
                $existing_types[$key];
                continue;
            }

            $new_type_parts = explode('&', $new_types[$key]);

            $result_type = isset($existing_types[$key])
                ? clone $existing_types[$key]
                : self::getValueForKey($key, $existing_types);

            if ($result_type && empty($result_type->types)) {
                throw new \InvalidArgumentException('Union::$types cannot be empty after get value for ' . $key);
            }

            foreach ($new_type_parts as $new_type_part) {
                $result_type = self::reconcileTypes(
                    (string) $new_type_part,
                    $result_type,
                    $key,
                    $code_location,
                    $suppressed_issues
                );

                // special case if result is just a simple array
                if ((string) $result_type === 'array') {
                    $result_type = Type::getArray();
                }
            }

            if ($result_type === null) {
                continue;
            }

            if ($result_type === false) {
                return false;
            }

            $existing_types[$key] = $result_type;
        }

        return $existing_types;
    }

    /**
     * Reconciles types
     *
     * think of this as a set of functions e.g. empty(T), notEmpty(T), null(T), notNull(T) etc. where
     *  - empty(Object) => null,
     *  - empty(bool) => false,
     *  - notEmpty(Object|null) => Object,
     *  - notEmpty(Object|false) => Object
     *
     * @param   string       $new_var_type
     * @param   Type\Union   $existing_var_type
     * @param   string       $key
     * @param   CodeLocation $code_location
     * @param   array        $suppressed_issues
     * @return  Type\Union|null|false
     */
    public static function reconcileTypes(
        $new_var_type,
        Type\Union $existing_var_type = null,
        $key = null,
        CodeLocation $code_location = null,
        array $suppressed_issues = []
    ) {
        $result_var_types = null;

        if ($existing_var_type === null) {
            if ($new_var_type[0] !== '!') {
                return Type::parseString($new_var_type);
            }

            return $new_var_type === '!empty' ? Type::getMixed() : null;
        }

        if ($new_var_type === 'mixed' && $existing_var_type->isMixed()) {
            return $existing_var_type;
        }

        if ($new_var_type[0] === '!') {
            if ($new_var_type === '!object' && !$existing_var_type->isMixed()) {
                $non_object_types = [];

                foreach ($existing_var_type->types as $type) {
                    if (!$type->isObjectType()) {
                        $non_object_types[] = $type;
                    }
                }

                if ($non_object_types) {
                    return new Type\Union($non_object_types);
                }
            }

            if (in_array($new_var_type, ['!empty', '!null', '!isset'])) {
                $existing_var_type->removeType('null');

                if ($new_var_type === '!empty') {
                    $existing_var_type->removeType('false');
                }

                if (empty($existing_var_type->types)) {
                    // @todo - I think there's a better way to handle this, but for the moment
                    // mixed will have to do.
                    return Type::getMixed();
                }

                return $existing_var_type;
            }

            $negated_type = substr($new_var_type, 1);

            $existing_var_type->removeType($negated_type);

            if (empty($existing_var_type->types)) {
                if ($key && $code_location) {
                    if (IssueBuffer::accepts(
                        new FailedTypeResolution('Cannot resolve types for ' . $key, $code_location),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }

                return Type::getMixed();
            }

            return $existing_var_type;
        }

        if ($new_var_type === 'empty') {
            if ($existing_var_type->hasType('bool')) {
                $existing_var_type->removeType('bool');
                $existing_var_type->types['false'] = new Type\Atomic('false');
            }

            $existing_var_type->removeObjects();

            if (empty($existing_var_type->types)) {
                return Type::getNull();
            }

            return $existing_var_type;
        }

        if ($new_var_type === 'object' && !$existing_var_type->isMixed()) {
            $object_types = [];

            foreach ($existing_var_type->types as $type) {
                if ($type->isObjectType()) {
                    $object_types[] = $type;
                }
            }

            if ($object_types) {
                return new Type\Union($object_types);
            }
        }

        if ($new_var_type === 'numeric' && $existing_var_type->hasString()) {
            $existing_var_type->removeType('string');
            $existing_var_type->types['numeric-string'] = new Type\Atomic('numeric-string');

            return $existing_var_type;
        }

        if ($new_var_type === 'isset') {
            return Type::getNull();
        }

        $new_type = Type::parseString($new_var_type);

        if ($existing_var_type->isMixed()) {
            return $new_type;
        }

        if (!TypeChecker::isContainedBy($new_type, $existing_var_type) &&
            !TypeChecker::isContainedBy($existing_var_type, $new_type) &&
            $code_location) {
            if (IssueBuffer::accepts(
                new TypeDoesNotContainType(
                    'Cannot resolve types for ' . $key . ' - ' . $existing_var_type . ' does not contain ' . $new_type,
                    $code_location
                ),
                $suppressed_issues
            )) {
                // fall through
            }
        }

        return $new_type;
    }

/**
     * Does the input param type match the given param type
     *
     * @param  Type\Union $input_type
     * @param  Type\Union $container_type
     * @param  bool       $ignore_null
     * @param  bool       &$has_scalar_match
     * @param  bool       &$type_coerced    whether or not there was type coercion involved
     * @return bool
     */
    public static function isContainedBy(
        Type\Union $input_type,
        Type\Union $container_type,
        $ignore_null = false,
        &$has_scalar_match = null,
        &$type_coerced = null
    ) {
        $has_scalar_match = true;

        if ($container_type->isMixed()) {
            return true;
        }

        $type_match_found = false;
        $has_type_mismatch = false;

        foreach ($input_type->types as $input_type_part) {
            if ($input_type_part->isNull() && $ignore_null) {
                continue;
            }

            $type_match_found = false;
            $scalar_type_match_found = false;

            foreach ($container_type->types as $container_type_part) {
                if ($container_type_part->isNull() && $ignore_null) {
                    continue;
                }

                if ($input_type_part->value === $container_type_part->value ||
                    ClassChecker::classExtendsOrImplements($input_type_part->value, $container_type_part->value) ||
                    ExpressionChecker::isMock($input_type_part->value)
                ) {
                    $all_types_contain = true;

                    if ($input_type_part instanceof Type\Generic && $container_type_part instanceof Type\Generic) {
                        foreach ($input_type_part->type_params as $i => $input_param) {
                            $container_param = $container_type_part->type_params[$i];

                            if (!$input_param->isEmpty() &&
                                !self::isContainedBy(
                                    $input_param,
                                    $container_param,
                                    $ignore_null,
                                    $has_scalar_match,
                                    $type_coerced
                                )
                            ) {
                                if (self::isContainedBy($container_param, $input_param)) {
                                    $type_coerced = true;
                                } else {
                                    $all_types_contain = false;
                                }
                            }
                        }
                    }

                    if ($all_types_contain) {
                        $type_match_found = true;
                    }

                    break;
                }

                if ($input_type_part->value === 'false' && $container_type_part->value === 'bool') {
                    $type_match_found = true;
                }

                if ($input_type_part->value === 'int' && $container_type_part->value === 'float') {
                    $type_match_found = true;
                }

                if ($input_type_part->value === 'Closure' && $container_type_part->value === 'callable') {
                    $type_match_found = true;
                }

                if ($container_type_part->isNumeric() && $input_type_part->isNumericType()) {
                    $type_match_found = true;
                }

                if ($container_type_part->isGenericArray() && $input_type_part->isObjectLike()) {
                    $type_match_found = true;
                }

                if ($container_type_part->isIterable() &&
                    (
                        $input_type_part->isArray() ||
                        ClassChecker::classExtendsOrImplements($input_type_part->value, 'Traversable')
                    )
                ) {
                    $type_match_found = true;
                }

                if ($container_type_part->isScalar() && $input_type_part->isScalarType()) {
                    $type_match_found = true;
                }

                if ($container_type_part->isString() && $input_type_part->isObjectType()) {
                    // check whether the object has a __toString method
                    if (MethodChecker::methodExists($input_type_part->value . '::__toString')) {
                        $type_match_found = true;
                    }
                }

                if ($container_type_part->isCallable() &&
                    ($input_type_part->value === 'string' || $input_type_part->value === 'array')
                ) {
                    // @todo add value checks if possible here
                    $type_match_found = true;
                }

                if ($input_type_part->isNumeric()) {
                    if ($container_type_part->isNumericType()) {
                        $scalar_type_match_found = true;
                    }
                }

                if ($input_type_part->isScalarType() || $input_type_part->isScalar()) {
                    if ($container_type_part->isScalarType()) {
                        $scalar_type_match_found = true;
                    }
                } elseif ($container_type_part->isObject() &&
                    !$input_type_part->isArray() &&
                    !$input_type_part->isResource()
                ) {
                    $type_match_found = true;
                }

                if (ClassChecker::classExtendsOrImplements($container_type_part->value, $input_type_part->value)) {
                    $type_coerced = true;
                    $type_match_found = true;
                    break;
                }
            }

            if (!$type_match_found) {
                if (!$scalar_type_match_found) {
                    $has_scalar_match = false;
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Gets the type for a given (non-existent key) based on the passed keys
     *
     * @param  string                    $key
     * @param  array<string,Type\Union>  $existing_keys
     * @return Type\Union|null
     */
    protected static function getValueForKey($key, array &$existing_keys)
    {
        $key_parts = explode('->', $key);

        $base_type = self::getArrayValueForKey($key_parts[0], $existing_keys);

        if (!$base_type) {
            return null;
        }

        $base_key = $key_parts[0];

        // for an expression like $obj->key1->key2
        for ($i = 1; $i < count($key_parts); $i++) {
            $new_base_key = $base_key . '->' . $key_parts[$i];

            if (!isset($existing_keys[$new_base_key])) {
                /** @var null|Type\Union */
                $new_base_type = null;

                foreach ($existing_keys[$base_key]->types as $existing_key_type_part) {
                    if ($existing_key_type_part->isNull()) {
                        $class_property_type = Type::getNull();
                    } else {
                        $class_properties = ClassLikeChecker::getInstancePropertiesForClass(
                            $existing_key_type_part->value,
                            \ReflectionProperty::IS_PRIVATE
                        );

                        if (!isset($class_properties[$key_parts[$i]])) {
                            return null;
                        }

                        $class_property_type = $class_properties[$key_parts[$i]];

                        $class_property_type = $class_property_type ? clone $class_property_type : Type::getMixed();
                    }

                    if ($new_base_type instanceof Type\Union) {
                        $new_base_type = Type::combineUnionTypes($new_base_type, $class_property_type);
                    } else {
                        $new_base_type = $class_property_type;
                    }

                    $existing_keys[$new_base_key] = $new_base_type;
                }
            }

            $base_type = $existing_keys[$new_base_key];
            $base_key = $new_base_key;
        }

        return $existing_keys[$base_key];
    }

    /**
     * Gets the type for a given (non-existent key) based on the passed keys
     *
     * @param  string                    $key
     * @param  array<string,Type\Union>  $existing_keys
     * @return Type\Union|null
     */
    protected static function getArrayValueForKey($key, array &$existing_keys)
    {
        $key_parts = preg_split('/(\]|\[)/', $key, -1, PREG_SPLIT_NO_EMPTY);

        if (count($key_parts) === 1) {
            return isset($existing_keys[$key_parts[0]]) ? clone $existing_keys[$key_parts[0]] : null;
        }

        if (!isset($existing_keys[$key_parts[0]])) {
            return null;
        }

        $base_type = $existing_keys[$key_parts[0]];
        $base_key = $key_parts[0];

        // for an expression like $obj->key1->key2
        for ($i = 1; $i < count($key_parts); $i++) {
            $new_base_key = $base_key . '[' . $key_parts[$i] . ']';

            if (!isset($existing_keys[$new_base_key])) {
                /** @var Type\Union|null */
                $new_base_type = null;

                foreach ($existing_keys[$base_key]->types as $existing_key_type_part) {
                    if ($existing_key_type_part instanceof Type\Generic) {
                        $new_base_type_candidate = clone $existing_key_type_part->type_params[1];
                    } elseif (!$existing_key_type_part instanceof Type\ObjectLike) {
                        return null;
                    } else {
                        $array_properties = $existing_key_type_part->properties;

                        $key_parts_key = str_replace('\'', '', $key_parts[$i]);

                        if (!isset($array_properties[$key_parts_key])) {
                            return null;
                        }

                        $new_base_type_candidate = clone $array_properties[$key_parts_key];
                    }

                    if (!$new_base_type) {
                        $new_base_type = $new_base_type_candidate;
                    } else {
                        $new_base_type = Type::combineUnionTypes(
                            $new_base_type,
                            $new_base_type_candidate
                        );
                    }

                    $existing_keys[$new_base_key] = $new_base_type;
                }
            }

            $base_type = $existing_keys[$new_base_key];
            $base_key = $new_base_key;
        }

        return $existing_keys[$base_key];
    }

    /**
     * @param   string  $type
     * @param   string  $existing_type
     * @return  bool
     */
    public static function isNegation($type, $existing_type)
    {
        if ($type === 'mixed' || $existing_type === 'mixed') {
            return false;
        }

        if ($type === '!' . $existing_type || $existing_type === '!' . $type) {
            return true;
        }

        if (in_array($type, ['empty', 'false', 'null']) && !in_array($existing_type, ['empty', 'false', 'null'])) {
            return true;
        }

        if (in_array($existing_type, ['empty', 'false', 'null']) && !in_array($type, ['empty', 'false', 'null'])) {
            return true;
        }

        return false;
    }

    /**
     * Takes two arrays of types and merges them
     *
     * @param  array<string, Type\Union>  $new_types
     * @param  array<string, Type\Union>  $existing_types
     * @return array<string, Type\Union>
     */
    public static function combineKeyedTypes(array $new_types, array $existing_types)
    {
        $keys = array_merge(array_keys($new_types), array_keys($existing_types));
        $keys = array_unique($keys);

        $result_types = [];

        if (empty($new_types)) {
            return $existing_types;
        }

        if (empty($existing_types)) {
            return $new_types;
        }

        foreach ($keys as $key) {
            if (!isset($existing_types[$key])) {
                $result_types[$key] = $new_types[$key];
                continue;
            }

            if (!isset($new_types[$key])) {
                $result_types[$key] = $existing_types[$key];
                continue;
            }

            $existing_var_types = $existing_types[$key];
            $new_var_types = $new_types[$key];

            if ((string) $new_var_types === (string) $existing_var_types) {
                $result_types[$key] = $new_var_types;
            } else {
                $result_types[$key] = Type::combineUnionTypes($new_var_types, $existing_var_types);
            }
        }

        return $result_types;
    }

    /**
     * @param  array<string, string>  $all_types
     * @return array<string>
     */
    public static function reduceTypes(array $all_types)
    {
        if (in_array('mixed', $all_types)) {
            return ['mixed'];
        }

        $array_types = array_filter(
            $all_types,
            /**
             * @param string $type
             * @return bool
             */
            function ($type) {
                return (bool)preg_match('/^array(\<|$)/', $type);
            }
        );

        $all_types = array_flip($all_types);

        if (isset($all_types['array<empty>']) && count($array_types) > 1) {
            unset($all_types['array<empty>']);
        }

        if (isset($all_types['array<mixed>'])) {
            unset($all_types['array<mixed>']);

            $all_types['array'] = true;
        }

        return array_keys($all_types);
    }

    /**
     * @param  array<string, string>  $types
     * @return array<string, string>
     */
    public static function negateTypes(array $types)
    {
        return array_map(
            /**
             * @param  string $type
             * @return  string
             */
            function ($type) {
                return self::negateType($type);
            },
            $types
        );
    }

    /**
     * @param  string $type
     * @return  string
     */
    protected static function negateType($type)
    {
        if ($type === 'mixed') {
            return $type;
        }

        $type_parts = explode('&', (string)$type);

        foreach ($type_parts as &$type_part) {
            $type_part = $type_part[0] === '!' ? substr($type_part, 1) : '!' . $type_part;
        }

        return implode('&', $type_parts);
    }

    /**
     * @param  Type\Union $declared_type
     * @param  Type\Union $inferred_type
     * @return boolean
     */
    public static function hasIdenticalTypes(Type\Union $declared_type, Type\Union $inferred_type)
    {
        if ($declared_type->isMixed() || $inferred_type->isEmpty()) {
            return true;
        }

        if ($declared_type->isNullable() !== $inferred_type->isNullable()) {
            return false;
        }

        $simple_declared_types = array_filter(
            array_keys($declared_type->types),
            /**
             * @param  string $type_value
             * @return  bool
             */
            function ($type_value) {
                return $type_value !== 'null';
            }
        );

        $simple_inferred_types = array_filter(
            array_keys($inferred_type->types),
            /**
             * @param  string $type_value
             * @return  bool
             */
            function ($type_value) {
                return $type_value !== 'null';
            }
        );

        // gets elements A△B
        $differing_types = array_diff($simple_inferred_types, $simple_declared_types);

        if (count($differing_types)) {
            // check whether the differing types are subclasses of declared return types
            $truly_different = false;

            foreach ($differing_types as $differing_type) {
                $is_match = false;

                if ($differing_type === 'mixed') {
                    continue;
                }

                foreach ($simple_declared_types as $simple_declared_type) {
                    if ($simple_declared_type === 'mixed'
                        || ($simple_declared_type === 'object' &&
                            ClassLikeChecker::classOrInterfaceExists($differing_type))
                        || ClassChecker::classExtendsOrImplements($differing_type, $simple_declared_type)
                        || (InterfaceChecker::interfaceExists($differing_type) &&
                            InterfaceChecker::interfaceExtends($differing_type, $simple_declared_type))
                        || (in_array($differing_type, ['float', 'int']) &&
                            in_array($simple_declared_type, ['float', 'int']))
                    ) {
                        $is_match = true;
                        break;
                    }
                }

                if (!$is_match) {
                    return false;
                }
            }
        }

        foreach ($declared_type->types as $key => $declared_atomic_type) {
            if (!isset($inferred_type->types[$key])) {
                continue;
            }

            $inferred_atomic_type = $inferred_type->types[$key];

            if (!($declared_atomic_type instanceof Type\Generic)) {
                continue;
            }

            if (!($inferred_atomic_type instanceof Type\Generic)) {
                // @todo handle this better
                continue;
            }

            foreach ($declared_atomic_type->type_params as $offset => $type_param) {
                if (!self::hasIdenticalTypes(
                    $declared_atomic_type->type_params[$offset],
                    $inferred_atomic_type->type_params[$offset]
                )) {
                    return false;
                }
            }
        }

        foreach ($declared_type->types as $key => $declared_atomic_type) {
            if (!isset($inferred_type->types[$key])) {
                continue;
            }

            $inferred_atomic_type = $inferred_type->types[$key];

            if (!($declared_atomic_type instanceof Type\ObjectLike)) {
                continue;
            }

            if (!($inferred_atomic_type instanceof Type\ObjectLike)) {
                // @todo handle this better
                continue;
            }

            foreach ($declared_atomic_type->properties as $property_name => $type_param) {
                if (!isset($inferred_atomic_type->properties[$property_name])) {
                    return false;
                }

                if (!self::hasIdenticalTypes(
                    $type_param,
                    $inferred_atomic_type->properties[$property_name]
                )) {
                    return false;
                }
            }
        }

        return true;
    }
}
