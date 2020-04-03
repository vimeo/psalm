<?php
namespace Psalm\Internal\Analyzer\FunctionLike;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Type;
use Psalm\Type\Atomic;
use function array_merge;
use function array_values;

/**
 * A class for analysing a given method call's effects in relation to $this/self and also looking at return types
 */
class ReturnTypeCollector
{
    /**
     * Gets the return types from a list of statements
     *
     * @param  array<PhpParser\Node>     $stmts
     * @param  list<Type\Atomic>         $yield_types
     * @param  bool                      $ignore_nullable_issues
     * @param  bool                      $ignore_falsable_issues
     * @param  bool                      $collapse_types
     *
     * @return list<Type\Atomic>    a list of return types
     */
    public static function getReturnTypes(
        \Psalm\Codebase $codebase,
        \Psalm\Internal\Provider\NodeDataProvider $nodes,
        array $stmts,
        array &$yield_types,
        bool &$ignore_nullable_issues = false,
        bool &$ignore_falsable_issues = false,
        bool $collapse_types = false
    ) {
        $return_types = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                if ($stmt->expr instanceof PhpParser\Node\Expr\Yield_ ||
                    $stmt->expr instanceof PhpParser\Node\Expr\YieldFrom) {
                    $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->expr, $nodes));
                }

                if (!$stmt->expr) {
                    $return_types[] = new Atomic\TVoid();
                } elseif ($stmt_type = $nodes->getType($stmt)) {
                    $return_types = array_merge(array_values($stmt_type->getAtomicTypes()), $return_types);

                    if ($stmt_type->ignore_nullable_issues) {
                        $ignore_nullable_issues = true;
                    }

                    if ($stmt_type->ignore_falsable_issues) {
                        $ignore_falsable_issues = true;
                    }
                } else {
                    $return_types[] = new Atomic\TMixed();
                }

                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Throw_
                || $stmt instanceof PhpParser\Node\Stmt\Break_
                || $stmt instanceof PhpParser\Node\Stmt\Continue_
            ) {
                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Expression
                && ($stmt->expr instanceof PhpParser\Node\Expr\Yield_
                    || $stmt->expr instanceof PhpParser\Node\Expr\YieldFrom)
            ) {
                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->expr, $nodes));
            } elseif ($stmt instanceof PhpParser\Node\Expr\Yield_
                || $stmt instanceof PhpParser\Node\Expr\YieldFrom
            ) {
                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt, $nodes));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Expression
                && $stmt->expr instanceof PhpParser\Node\Expr\Assign
            ) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $codebase,
                        $nodes,
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
                    $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($arg->value, $nodes));
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $codebase,
                        $nodes,
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
                            $codebase,
                            $nodes,
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
                            $codebase,
                            $nodes,
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
                        $codebase,
                        $nodes,
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
                            $codebase,
                            $nodes,
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
                            $codebase,
                            $nodes,
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
                        $codebase,
                        $nodes,
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
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types,
                        $ignore_nullable_issues,
                        $ignore_falsable_issues
                    )
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->cond, $nodes));
                $return_types = array_merge(
                    $return_types,
                    self::getReturnTypes(
                        $codebase,
                        $nodes,
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
                        $codebase,
                        $nodes,
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
                            $codebase,
                            $nodes,
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
                    if ($type instanceof Type\Atomic\ObjectLike) {
                        $type = $type->getGenericArrayType();
                    }

                    if ($type instanceof Type\Atomic\TList) {
                        $type = new Type\Atomic\TArray([Type::getInt(), $type->type_param]);
                    }

                    if ($type instanceof Type\Atomic\TArray) {
                        $key_type_param = $type->type_params[0];
                        $value_type_param = $type->type_params[1];

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
                    } elseif ($type instanceof Type\Atomic\TIterable
                        || $type instanceof Type\Atomic\TNamedObject
                    ) {
                        ForeachAnalyzer::getKeyValueParamsForTraversableObject(
                            $type,
                            $codebase,
                            $key_type,
                            $value_type
                        );
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
     * @return  list<Atomic>
     */
    protected static function getYieldTypeFromExpression(
        PhpParser\Node\Expr $stmt,
        \Psalm\Internal\Provider\NodeDataProvider $nodes
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            $key_type = null;

            if ($stmt->key && ($stmt_key_type = $nodes->getType($stmt->key))) {
                $key_type = $stmt_key_type;
            }

            if ($stmt->value
                && $value_type = $nodes->getType($stmt->value)
            ) {
                $generator_type = new Atomic\TGenericObject(
                    'Generator',
                    [
                        $key_type ? clone $key_type : Type::getInt(),
                        clone $value_type,
                        Type::getMixed(),
                        Type::getMixed()
                    ]
                );

                return [$generator_type];
            }

            return [new Atomic\TMixed()];
        } elseif ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            if ($stmt_expr_type = $nodes->getType($stmt->expr)) {
                return array_values($stmt_expr_type->getAtomicTypes());
            }

            return [new Atomic\TMixed()];
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            return array_merge(
                self::getYieldTypeFromExpression($stmt->left, $nodes),
                self::getYieldTypeFromExpression($stmt->right, $nodes)
            );
        } elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            return self::getYieldTypeFromExpression($stmt->expr, $nodes);
        }

        return [];
    }
}
