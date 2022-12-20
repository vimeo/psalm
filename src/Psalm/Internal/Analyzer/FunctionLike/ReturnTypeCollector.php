<?php

namespace Psalm\Internal\Analyzer\FunctionLike;

use PhpParser;
use PhpParser\NodeTraverser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\PhpVisitor\YieldTypeCollector;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_merge;

/**
 * A class for analysing a given method call's effects in relation to $this/self and also looking at return types
 *
 * @internal
 */
class ReturnTypeCollector
{
    /**
     * Gets the return types from a list of statements
     *
     * @param  array<PhpParser\Node>     $stmts
     * @param  list<Union>               $yield_types
     * @return list<Union>               a list of return types
     * @psalm-suppress ComplexMethod to be refactored
     */
    public static function getReturnTypes(
        Codebase $codebase,
        NodeDataProvider $nodes,
        array $stmts,
        array &$yield_types,
        bool $collapse_types = false
    ): array {
        $return_types = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                if (!$stmt->expr) {
                    $return_types[] = Type::getVoid();
                } elseif ($stmt_type = $nodes->getType($stmt)) {
                    $return_types[] = $stmt_type;

                    $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->expr, $nodes));
                } elseif ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
                    $return_types[] = Type::getString();
                } elseif ($stmt->expr instanceof PhpParser\Node\Scalar\LNumber) {
                    $return_types[] = Type::getInt();
                } elseif ($stmt->expr instanceof PhpParser\Node\Expr\ConstFetch) {
                    if ((string)$stmt->expr->name === 'true') {
                        $return_types[] = Type::getTrue();
                    } elseif ((string)$stmt->expr->name === 'false') {
                        $return_types[] = Type::getFalse();
                    } elseif ((string)$stmt->expr->name === 'null') {
                        $return_types[] = Type::getNull();
                    }
                } else {
                    $return_types[] = Type::getMixed();
                }

                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Break_
                || $stmt instanceof PhpParser\Node\Stmt\Continue_
            ) {
                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
                $return_types[] = Type::getNever();

                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Expression) {
                if ($stmt->expr instanceof PhpParser\Node\Expr\Exit_) {
                    $return_types[] = Type::getNever();

                    break;
                }

                if ($stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                    || $stmt->expr instanceof PhpParser\Node\Expr\StaticCall) {
                    $stmt_type = $nodes->getType($stmt->expr);
                    if ($stmt_type && ($stmt_type->isNever() || $stmt_type->explicit_never)) {
                        $return_types[] = Type::getNever();

                        break;
                    }
                }

                if ($stmt->expr instanceof PhpParser\Node\Expr\Assign) {
                    $return_types = [
                        ...$return_types,
                        ...self::getReturnTypes(
                            $codebase,
                            $nodes,
                            [$stmt->expr->expr],
                            $yield_types,
                        ),
                    ];
                }

                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->expr, $nodes));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $return_types = [
                    ...$return_types,
                    ...self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types,
                    ),
                ];

                foreach ($stmt->elseifs as $elseif) {
                    $return_types = [
                        ...$return_types,
                        ...self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $elseif->stmts,
                            $yield_types,
                        ),
                    ];
                }

                if ($stmt->else) {
                    $return_types = [
                        ...$return_types,
                        ...self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $stmt->else->stmts,
                            $yield_types,
                        ),
                    ];
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $return_types = [
                    ...$return_types,
                    ...self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types,
                    ),
                ];

                foreach ($stmt->catches as $catch) {
                    $return_types = [
                        ...$return_types,
                        ...self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $catch->stmts,
                            $yield_types,
                        ),
                    ];
                }

                if ($stmt->finally) {
                    $return_types = [
                        ...$return_types,
                        ...self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $stmt->finally->stmts,
                            $yield_types,
                        ),
                    ];
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $return_types = [
                    ...$return_types,
                    ...self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types,
                    ),
                ];
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $return_types = [
                    ...$return_types,
                    ...self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types,
                    ),
                ];
            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $yield_types = array_merge($yield_types, self::getYieldTypeFromExpression($stmt->cond, $nodes));
                $return_types = [
                    ...$return_types,
                    ...self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types,
                    ),
                ];
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $return_types = [
                    ...$return_types,
                    ...self::getReturnTypes(
                        $codebase,
                        $nodes,
                        $stmt->stmts,
                        $yield_types,
                    ),
                ];
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                foreach ($stmt->cases as $case) {
                    $return_types = [
                        ...$return_types,
                        ...self::getReturnTypes(
                            $codebase,
                            $nodes,
                            $case->stmts,
                            $yield_types,
                        ),
                    ];
                }
            }
        }

        // if we're at the top level and we're not ending in a return, make sure to add possible null
        if ($collapse_types) {
            // if it's a generator, boil everything down to a single generator return type
            if ($yield_types) {
                $yield_types = self::processYieldTypes($codebase, $return_types, $yield_types);
            }
        }

        return $return_types;
    }

    /**
     * @param  list<Union>           $return_types
     * @param  non-empty-list<Union> $yield_types
     * @return non-empty-list<Union>
     */
    private static function processYieldTypes(
        Codebase $codebase,
        array $return_types,
        array $yield_types
    ): array {
        $key_type = null;
        $value_type = null;

        $yield_type = Type::combineUnionTypeArray($yield_types, null);

        foreach ($yield_type->getAtomicTypes() as $type) {
            if ($type instanceof TList) {
                $type = $type->getKeyedArray();
            }

            if ($type instanceof TKeyedArray) {
                $type = $type->getGenericArrayType();
            }

            if ($type instanceof TArray) {
                [$key_type_param, $value_type_param] = $type->type_params;

                $key_type = Type::combineUnionTypes($key_type_param, $key_type);
                $value_type = Type::combineUnionTypes($value_type_param, $value_type);
            } elseif ($type instanceof TIterable
                || $type instanceof TNamedObject
            ) {
                ForeachAnalyzer::getKeyValueParamsForTraversableObject(
                    $type,
                    $codebase,
                    $key_type,
                    $value_type,
                );
            }
        }

        return [
            new Union([
                new TGenericObject(
                    'Generator',
                    [
                        $key_type ?? Type::getMixed(),
                        $value_type ?? Type::getMixed(),
                        Type::getMixed(),
                        $return_types ? Type::combineUnionTypeArray($return_types, null) : Type::getVoid(),
                    ],
                ),
            ]),
        ];
    }

    /**
     * @return list<Union>
     */
    private static function getYieldTypeFromExpression(
        PhpParser\Node\Expr $stmt,
        NodeDataProvider $nodes
    ): array {
        $collector = new YieldTypeCollector($nodes);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            $collector,
        );
        $traverser->traverse([$stmt]);

        return $collector->getYieldTypes();
    }
}
