<?php

namespace Psalm;

use PhpParser;

/**
 * A class for analysing a given method call's effects in relation to
 * $this/self and also looking at return types
 */
class EffectsAnalyser
{
    /**
     * Gets the return types from a list of statements
     *
     * @param  array<int,PhpParser\Node\Stmt>  $stmts
     * @return array<int,Type\Atomic>    a list of return types
     */
    public static function getReturnTypes(array $stmts, $collapse_types = false)
    {
        /** @var array<int,Type\Atomic> */
        $return_types = [];

        $last_stmt = null;

        foreach ($stmts as $stmt) {
            if (!$stmt instanceof PhpParser\Node\Stmt\Nop) {
                $last_stmt = $stmt;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                if (isset($stmt->inferredType)) {
                    $return_types = array_merge(array_values($stmt->inferredType->types), $return_types);
                }
                else {
                    $return_types[] = new Type\Atomic('mixed');
                }

            }
            elseif ($stmt instanceof PhpParser\Node\Expr\Yield_) {
                $key_type = null;

                if (isset($stmt->key->inferredType)) {
                    $key_type = $stmt->key->inferredType;
                }

                if (isset($stmt->inferredType)) {
                    $generator_type = new Type\Generic(
                        'Generator',
                        [
                            $key_type ?: Type::getInt(),
                            $stmt->inferredType
                        ]
                    );

                    $return_types = array_merge([$generator_type], $return_types);
                }
                else {
                    $return_types[] = new Type\Atomic('mixed');
                }

            }
            elseif ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
                $key_type = null;

                if (isset($stmt->inferredType)) {
                    $return_types = array_merge(array_values($stmt->inferredType->types), $return_types);
                }
                else {
                    $return_types[] = new Type\Atomic('mixed');
                }

            }
            elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

                foreach ($stmt->elseifs as $elseif) {
                    $return_types = array_merge($return_types, self::getReturnTypes($elseif->stmts));
                }

                if ($stmt->else) {
                    $return_types = array_merge($return_types, self::getReturnTypes($stmt->else->stmts));
                }

            }
            elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

                foreach ($stmt->catches as $catch) {
                    $return_types = array_merge($return_types, self::getReturnTypes($catch->stmts));
                }

                if ($stmt->finallyStmts) {
                    $return_types = array_merge($return_types, self::getReturnTypes($stmt->finallyStmts));
                }

            }
            elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

            }
            elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

            }
            elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

            }
            elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

            }
            elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                foreach ($stmt->cases as $case) {
                    $return_types = array_merge($return_types, self::getReturnTypes($case->stmts));
                }
            }
        }

        // if we're at the top level and we're not ending in a return, make sure to add possible null
        if ($collapse_types) {
            $has_generator = false;

            foreach ($return_types as $return_type) {
                if ($return_type->isGenerator()) {
                    $has_generator = true;
                }
            }

            // if it's a generator, boil everything down to a single generator return type
            if ($has_generator) {
                $key_type = null;
                $value_type = null;

                foreach ($return_types as $type) {
                    if ($type instanceof Type\Generic) {
                        $first_type_param = count($type->type_params) ? $type->type_params[0] : null;
                        $last_type_param = $type->type_params[count($type->type_params) - 1];

                        if ($value_type === null) {
                            $value_type = clone $last_type_param;
                        }
                        else {
                            $value_type = Type::combineUnionTypes($value_type, $last_type_param);
                        }

                        if (!$key_type || !$first_type_param) {
                            $key_type = $first_type_param ? clone $first_type_param : Type::getMixed();
                        }
                        else {
                            $key_type = Type::combineUnionTypes($key_type, $first_type_param);
                        }
                    }
                }

                $return_types = [
                    new Type\Generic(
                        'Generator',
                        [
                            $key_type,
                            $value_type
                        ]
                    )
                ];
            }
            else {
                if (!$last_stmt instanceof PhpParser\Node\Stmt\Return_ && !Checker\ScopeChecker::doesAlwaysReturnOrThrow($stmts)) {
                    $return_types[] = new Type\Atomic('null');
                }
            }
        }

        return $return_types;
    }
}
