<?php
namespace Psalm\Checker\FunctionLike;

use PhpParser;
use Psalm\Type;
use Psalm\Type\Atomic;

/**
 * A class for analysing a given method call's effects in relation to $this/self and also looking at return types
 */
class ReturnTypeCollector
{
    /**
     * Gets the return types from a list of statements
     *
     * @param  array<PhpParser\Node>     $stmts
     * @param  array<int,Type\Atomic>    $yield_types
     * @param  bool                      $ignore_nullable_issues
     * @param  bool                      $ignore_falsable_issues
     * @param  bool                      $collapse_types
     *
     * @return array<int,Type\Atomic>    a list of return types
     */
    public static function getReturnTypes(
        array $stmts,
        array &$yield_types,
        &$ignore_nullable_issues = false,
        &$ignore_falsable_issues = false,
        $collapse_types = false
    ) {
        $return_types = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                if ($stmt->expr instanceof PhpParser\Node\Expr\Yield_ ||
                    $stmt->expr instanceof PhpParser\Node\Expr\YieldFrom) {
                    $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->expr));
                }

                if (!$stmt->expr) {
                    $return_types[] = new Atomic\TVoid();
                } elseif (isset($stmt->inferredType)) {
                    $return_types = array_merge(array_values($stmt->inferredType->getTypes()), $return_types);

                    if ($stmt->inferredType->ignore_nullable_issues) {
                        $ignore_nullable_issues = true;
                    }

                    if ($stmt->inferredType->ignore_falsable_issues) {
                        $ignore_falsable_issues = true;
                    }
                } else {
                    $return_types[] = new Atomic\TMixed();
                }

                break;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Throw_
                || $stmt instanceof PhpParser\Node\Stmt\Break_
                || $stmt instanceof PhpParser\Node\Stmt\Continue_
            ) {
                break;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Expression
                && ($stmt->expr instanceof PhpParser\Node\Expr\Yield_
                    || $stmt->expr instanceof PhpParser\Node\Expr\YieldFrom)
            ) {
                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->expr));
            } elseif ($stmt instanceof PhpParser\Node\Expr\Yield_
                || $stmt instanceof PhpParser\Node\Expr\YieldFrom
            ) {
                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Expression
                && $stmt->expr instanceof PhpParser\Node\Expr\Assign
            ) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        [$stmt->expr->expr],
                        $yield_types,
                        $ignore_nullable_issues,
                        $ignore_falsable_issues
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Expression
                && ($stmt->expr instanceof PhpParser\Node\Expr\MethodCall
                    || $stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                    || $stmt->expr instanceof PhpParser\Node\Expr\StaticCall
                )
            ) {
                foreach ($stmt->expr->args as $arg) {
                    $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($arg->value));
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $stmt->stmts,
                        $yield_types,
                        $ignore_nullable_issues,
                        $ignore_falsable_issues
                    )
                );

                foreach ($stmt->elseifs as $elseif) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $elseif->stmts,
                            $yield_types,
                            $ignore_nullable_issues,
                            $ignore_falsable_issues
                        )
                    );
                }

                if ($stmt->else) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $stmt->else->stmts,
                            $yield_types,
                            $ignore_nullable_issues,
                            $ignore_falsable_issues
                        )
                    );
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $stmt->stmts,
                        $yield_types,
                        $ignore_nullable_issues,
                        $ignore_falsable_issues
                    )
                );

                foreach ($stmt->catches as $catch) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $catch->stmts,
                            $yield_types,
                            $ignore_nullable_issues,
                            $ignore_falsable_issues
                        )
                    );
                }

                if ($stmt->finally) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $stmt->finally->stmts,
                            $yield_types,
                            $ignore_nullable_issues,
                            $ignore_falsable_issues
                        )
                    );
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $stmt->stmts,
                        $yield_types,
                        $ignore_nullable_issues,
                        $ignore_falsable_issues
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $stmt->stmts,
                        $yield_types,
                        $ignore_nullable_issues,
                        $ignore_falsable_issues
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $stmt->stmts,
                        $yield_types,
                        $ignore_nullable_issues,
                        $ignore_falsable_issues
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $stmt->stmts,
                        $yield_types,
                        $ignore_nullable_issues,
                        $ignore_falsable_issues
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                foreach ($stmt->cases as $case) {
                    $return_types = array_merge(
                        $return_types,
                        self::getReturnTypes(
                            $case->stmts,
                            $yield_types,
                            $ignore_nullable_issues,
                            $ignore_falsable_issues
                        )
                    );
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
                        switch (count($type->type_params)) {
                            case 1:
                                $key_type_param = Type::getMixed();
                                $value_type_param = $type->type_params[1];
                                break;

                            default:
                                $key_type_param = $type->type_params[0];
                                $value_type_param = $type->type_params[1];
                        }

                        if (!$key_type) {
                            $key_type = clone $key_type_param;
                        } else {
                            $key_type = Type::combineUnionTypes($key_type_param, $key_type);
                        }

                        if (!$value_type) {
                            $value_type = clone $value_type_param;
                        } else {
                            $value_type = Type::combineUnionTypes($value_type_param, $value_type);
                        }
                    }
                }

                $yield_types = [
                    new Atomic\TGenericObject(
                        'Generator',
                        [
                            $key_type ?: Type::getMixed(),
                            $value_type ?: Type::getMixed(),
                            Type::getMixed(),
                            $return_types ? new Type\Union($return_types) : Type::getVoid()
                        ]
                    ),
                ];
            }
        }

        return $return_types;
    }

    /**
     * @param   PhpParser\Node\Expr $stmt
     *
     * @return  array<int, Atomic>
     */
    protected static function getYieldTypeFromExpression(PhpParser\Node\Expr $stmt)
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
                        $stmt->inferredType,
                    ]
                );

                return [$generator_type];
            }

            return [new Atomic\TMixed()];
        } elseif ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            if (isset($stmt->expr->inferredType)) {
                return array_values($stmt->expr->inferredType->getTypes());
            }

            return [new Atomic\TMixed()];
        }

        return [];
    }
}
