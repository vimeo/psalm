<?php
namespace Psalm\Internal\Scanner;

use PhpParser;
use PhpParser\NodeTraverser;
use Psalm\Context;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\FileSource;
use Psalm\Storage\FileStorage;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Visitor\ReflectorVisitor;
use Psalm\Type;

/**
 * @internal
 */
class PhpStormMetaScanner
{
    /**
     * @param  array<PhpParser\Node\Stmt>  $stmts
     * @return void
     */
    public static function scan(array $stmts, Codebase $codebase)
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Namespace_
                && $stmt->name
                && $stmt->name->parts === ['PHPSTORM_META']
            ) {
                foreach ($stmt->stmts as $meta_stmt) {
                    if ($meta_stmt instanceof PhpParser\Node\Stmt\Expression
                        && $meta_stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                        && $meta_stmt->expr->name instanceof PhpParser\Node\Name
                        && $meta_stmt->expr->name->parts === ['override']
                        && count($meta_stmt->expr->args) > 1
                    ) {
                        self::handleOverride($meta_stmt->expr->args, $codebase);
                    }
                }
            }
        }
    }

    /**
     * @param  array<PhpParser\Node\Arg> $args
     * @return void
     */
    private static function handleOverride(array $args, Codebase $codebase)
    {
        $identifier = $args[0]->value;

        if (!$args[1]->value instanceof PhpParser\Node\Expr\FuncCall
            || !$args[1]->value->name instanceof PhpParser\Node\Name
        ) {
            return;
        }

        $map = [];

        if ($args[1]->value->name->parts === ['map']
            && $args[1]->value->args
            && $args[1]->value->args[0]->value instanceof PhpParser\Node\Expr\Array_
        ) {
            foreach ($args[1]->value->args[0]->value->items as $array_item) {
                if ($array_item
                    && $array_item->key instanceof PhpParser\Node\Scalar\String_
                ) {
                    if ($array_item->value instanceof PhpParser\Node\Expr\ClassConstFetch
                        && $array_item->value->class instanceof PhpParser\Node\Name\FullyQualified
                        && $array_item->value->name instanceof PhpParser\Node\Identifier
                        && strtolower($array_item->value->name->name)
                    ) {
                        $map[$array_item->key->value] = new Type\Union([
                            new Type\Atomic\TNamedObject(implode('\\', $array_item->value->class->parts))
                        ]);
                    }
                }
            }
        }

        $type_offset = null;

        if ($args[1]->value->name->parts === ['type']
            && $args[1]->value->args
            && $args[1]->value->args[0]->value instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $type_offset = $args[1]->value->args[0]->value->value;
        }

        $element_type_offset = null;

        if ($args[1]->value->name->parts === ['elementType']
            && $args[1]->value->args
            && $args[1]->value->args[0]->value instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $element_type_offset = $args[1]->value->args[0]->value->value;
        }

        if ($identifier instanceof PhpParser\Node\Expr\StaticCall
            && $identifier->class instanceof PhpParser\Node\Name\FullyQualified
            && $identifier->name instanceof PhpParser\Node\Identifier
            && $identifier->args
            && $identifier->args[0]->value instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $method_id = implode('\\', $identifier->class->parts) . '::' . $identifier->name;

            if ($map) {
                $offset = $identifier->args[0]->value->value;

                $codebase->methods->return_type_provider->registerClosure(
                    $method_id,
                    /**
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        StatementsAnalyzer $statements_analyzer,
                        string $method_id,
                        string $_appearing_method_id,
                        string $_declaring_method_id,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $offset
                    ) : Type\Union {
                        if (($call_arg_type = $call_args[$offset]->value->inferredType ?? null)
                            && $call_arg_type->isSingleStringLiteral()
                        ) {
                            $offset_arg_value = $call_arg_type->getSingleStringLiteral()->value;

                            if (isset($map[$offset_arg_value])) {
                                return clone $map[$offset_arg_value];
                            }
                        }

                        $storage = $statements_analyzer->getCodebase()->methods->getStorage(
                            $method_id
                        );

                        return $storage->return_type ?: Type::getMixed();
                    }
                );
            } elseif ($type_offset !== null) {
                $codebase->methods->return_type_provider->registerClosure(
                    $method_id,
                    /**
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        StatementsAnalyzer $statements_analyzer,
                        string $method_id,
                        string $_appearing_method_id,
                        string $_declaring_method_id,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $type_offset
                    ) : Type\Union {
                        if (($call_arg_type = $call_args[$type_offset]->value->inferredType ?? null)) {
                            return clone $call_arg_type;
                        }

                        $storage = $statements_analyzer->getCodebase()->methods->getStorage(
                            $method_id
                        );

                        return $storage->return_type ?: Type::getMixed();
                    }
                );
            } elseif ($element_type_offset !== null) {
                $codebase->methods->return_type_provider->registerClosure(
                    $method_id,
                    /**
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        StatementsAnalyzer $statements_analyzer,
                        string $method_id,
                        string $_appearing_method_id,
                        string $_declaring_method_id,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $element_type_offset
                    ) : Type\Union {
                        if (($call_arg_type = $call_args[$element_type_offset]->value->inferredType ?? null)) {
                            if ($call_arg_type->hasArray()) {
                                /** @var Type\Atomic\TArray|Type\Atomic\ObjectLike */
                                $array_atomic_type = $call_arg_type->getTypes()['array'];

                                if ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                                    return $array_atomic_type->getGenericValueType();
                                }

                                return clone $array_atomic_type->type_params[1];
                            }
                        }

                        $storage = $statements_analyzer->getCodebase()->methods->getStorage(
                            $method_id
                        );

                        return $storage->return_type ?: Type::getMixed();
                    }
                );
            }
        }

        if ($identifier instanceof PhpParser\Node\Expr\FuncCall
            && $identifier->name instanceof PhpParser\Node\Name\FullyQualified
            && $identifier->args
            && $identifier->args[0]->value instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $function_id = implode('\\', $identifier->name->parts);

            if ($map) {
                $offset = $identifier->args[0]->value->value;

                $codebase->functions->return_type_provider->registerClosure(
                    $function_id,
                    /**
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        StatementsAnalyzer $statements_analyzer,
                        string $function_id,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $offset
                    ) : Type\Union {
                        if (($call_arg_type = $call_args[$offset]->value->inferredType ?? null)
                            && $call_arg_type->isSingleStringLiteral()
                        ) {
                            $offset_arg_value = $call_arg_type->getSingleStringLiteral()->value;

                            if (isset($map[$offset_arg_value])) {
                                return clone $map[$offset_arg_value];
                            }
                        }

                        $storage = $statements_analyzer->getCodebase()->functions->getStorage(
                            $statements_analyzer,
                            $function_id
                        );

                        return $storage->return_type ?: Type::getMixed();
                    }
                );
            } elseif ($type_offset !== null) {
                $codebase->functions->return_type_provider->registerClosure(
                    $function_id,
                    /**
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        StatementsAnalyzer $statements_analyzer,
                        string $function_id,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $type_offset
                    ) : Type\Union {
                        if (($call_arg_type = $call_args[$type_offset]->value->inferredType ?? null)) {
                            return clone $call_arg_type;
                        }

                        $storage = $statements_analyzer->getCodebase()->methods->getStorage(
                            $function_id
                        );

                        return $storage->return_type ?: Type::getMixed();
                    }
                );
            } elseif ($element_type_offset !== null) {
                $codebase->functions->return_type_provider->registerClosure(
                    $function_id,
                    /**
                     * @param array<PhpParser\Node\Arg> $call_args
                     */
                    function (
                        StatementsAnalyzer $statements_analyzer,
                        string $function_id,
                        array $call_args,
                        Context $_context,
                        CodeLocation $_code_location
                    ) use (
                        $map,
                        $element_type_offset
                    ) : Type\Union {
                        if (($call_arg_type = $call_args[$element_type_offset]->value->inferredType ?? null)) {
                            if ($call_arg_type->hasArray()) {
                                /** @var Type\Atomic\TArray|Type\Atomic\ObjectLike */
                                $array_atomic_type = $call_arg_type->getTypes()['array'];

                                if ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                                    return $array_atomic_type->getGenericValueType();
                                }

                                return clone $array_atomic_type->type_params[1];
                            }
                        }

                        $storage = $statements_analyzer->getCodebase()->methods->getStorage(
                            $function_id
                        );

                        return $storage->return_type ?: Type::getMixed();
                    }
                );
            }
        }
    }
}
