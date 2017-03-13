<?php
namespace Psalm;

use PhpParser;
use Psalm\Type\Atomic;

/**
 * A class for analysing a given method call's effects in relation to $this/self and also looking at return types
 *
 */
class EffectsAnalyser
{
    /**
     * Gets the return types from a list of statements
     *
     * @param  array<PhpParser\Node>     $stmts
     * @param  array<int,Type\Atomic>    $yield_types
     * @param  bool                      $collapse_types
     * @return array<int,Type\Atomic>    a list of return types
     */
    public static function getReturnTypes(array $stmts, array &$yield_types, $collapse_types = false)
    {
        $return_types = [];

        $last_stmt = null;

        foreach ($stmts as $stmt) {
            if (!$stmt instanceof PhpParser\Node\Stmt\Nop) {
                $last_stmt = $stmt;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                if ($stmt->expr instanceof PhpParser\Node\Expr\Yield_ ||
                    $stmt->expr instanceof PhpParser\Node\Expr\YieldFrom) {
                    $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->expr));
                } else {
                    if (isset($stmt->inferredType)) {
                        $return_types = array_merge(array_values($stmt->inferredType->types), $return_types);
                    } else {
                        $return_types[] = new Atomic\TMixed();
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Expr\Yield_ || $stmt instanceof PhpParser\Node\Expr\YieldFrom) {
                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts, $yield_types));

                foreach ($stmt->elseifs as $elseif) {
                    $return_types = array_merge($return_types, self::getReturnTypes($elseif->stmts, $yield_types));
                }

                if ($stmt->else) {
                    $return_types = array_merge($return_types, self::getReturnTypes($stmt->else->stmts, $yield_types));
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts, $yield_types));

                foreach ($stmt->catches as $catch) {
                    $return_types = array_merge($return_types, self::getReturnTypes($catch->stmts, $yield_types));
                }

                if ($stmt->finally) {
                    $return_types = array_merge($return_types, self::getReturnTypes($stmt->finally->stmts, $yield_types));
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts, $yield_types));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts, $yield_types));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts, $yield_types));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts, $yield_types));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                foreach ($stmt->cases as $case) {
                    $return_types = array_merge($return_types, self::getReturnTypes($case->stmts, $yield_types));
                }
            }
        }

        // if we're at the top level and we're not ending in a return, make sure to add possible null
        if ($collapse_types) {
            // if it's a generator, boil everything down to a single generator return type
            if ($yield_types) {
                $key_type = null;
                $value_type = null;

                foreach ($yield_types as $type) {
                    if ($type instanceof Type\Atomic\TArray || $type instanceof Type\Atomic\TGenericObject) {
                        $first_type_param = count($type->type_params) ? $type->type_params[0] : null;
                        $last_type_param = $type->type_params[count($type->type_params) - 1];

                        if ($value_type === null) {
                            $value_type = clone $last_type_param;
                        } else {
                            $value_type = Type::combineUnionTypes($value_type, $last_type_param);
                        }

                        if (!$key_type || !$first_type_param) {
                            $key_type = $first_type_param ? clone $first_type_param : Type::getMixed();
                        } else {
                            $key_type = Type::combineUnionTypes($key_type, $first_type_param);
                        }
                    }
                }

                $yield_types = [
                    new Atomic\TGenericObject(
                        'Generator',
                        [
                            $key_type ?: Type::getMixed(),
                            $value_type ?: Type::getMixed()
                        ]
                    )
                ];
            }

            if (!$last_stmt instanceof PhpParser\Node\Stmt\Return_ &&
                !Checker\ScopeChecker::doesAlwaysReturnOrThrow($stmts) &&
                !$yield_types &&
                count($return_types)
            ) {
                // only add null if we have a return statement elsewhere and it wasn't void
                foreach ($return_types as $return_type) {
                    if (!$return_type instanceof Atomic\TVoid) {
                        $return_types[] = new Atomic\TNull();
                        break;
                    }
                }
            }
        }

        return $return_types;
    }

    /**
     * @param   PhpParser\Node\Expr $stmt
     * @return  array<int, Atomic>
     */
    protected static function getYieldTypeFromExpression($stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            $key_type = null;

            if (isset($stmt->key->inferredType)) {
                $key_type = $stmt->key->inferredType;
            }

            if (isset($stmt->inferredType)) {
                $generator_type = new Atomic\TGenericObject(
                    'Generator',
                    [
                        $key_type ?: Type::getInt(),
                        $stmt->inferredType
                    ]
                );

                return [$generator_type];
            } else {
                return [new Atomic\TMixed()];
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            $key_type = null;

            if (isset($stmt->inferredType)) {
                return array_values($stmt->inferredType->types);
            } else {
                return [new Atomic\TMixed()];
            }
        }

        return [];
    }
}
