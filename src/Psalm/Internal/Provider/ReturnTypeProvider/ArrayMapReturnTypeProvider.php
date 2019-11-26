<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function array_map;
use function count;
use function explode;
use function in_array;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Codebase\CallMap;
use Psalm\Internal\Type\ArrayType;
use Psalm\StatementsSource;
use Psalm\Type;
use function strpos;
use function strtolower;

class ArrayMapReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_map'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @param  CodeLocation                 $code_location
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        $array_arg = isset($call_args[1]->value) ? $call_args[1]->value : null;

        $array_arg_atomic_type = null;
        $array_arg_type = null;

        if ($array_arg && ($array_arg_union_type = $statements_source->node_data->getType($array_arg))) {
            $arg_types = $array_arg_union_type->getTypes();

            if (isset($arg_types['array'])) {
                $array_arg_atomic_type = $arg_types['array'];
                $array_arg_type = ArrayType::infer($array_arg_atomic_type);
            }
        }

        if (isset($call_args[0])) {
            $function_call_arg = $call_args[0];

            if (count($call_args) === 2) {
                $generic_key_type = $array_arg_type->key ?? Type::getArrayKey();
            } else {
                $generic_key_type = Type::getInt();
            }

            if (($function_call_type = $statements_source->node_data->getType($function_call_arg->value))
                && ($closure_types = $function_call_type->getClosureTypes())
            ) {
                $closure_atomic_type = \reset($closure_types);
                $closure_return_type = $closure_atomic_type->return_type ?: Type::getMixed();

                if ($closure_return_type->isVoid()) {
                    $closure_return_type = Type::getNull();
                }

                $inner_type = clone $closure_return_type;

                if ($array_arg_atomic_type instanceof Type\Atomic\ObjectLike && count($call_args) === 2) {
                    $atomic_type = new Type\Atomic\ObjectLike(
                        array_map(
                            /**
                            * @return Type\Union
                            */
                            function (Type\Union $_) use ($inner_type) {
                                return clone $inner_type;
                            },
                            $array_arg_atomic_type->properties
                        )
                    );
                    $atomic_type->is_list = $array_arg_atomic_type->is_list;

                    return new Type\Union([$atomic_type]);
                }

                if ($array_arg_atomic_type instanceof Type\Atomic\TList) {
                    if ($array_arg_atomic_type instanceof Type\Atomic\TNonEmptyList) {
                        return new Type\Union([
                            new Type\Atomic\TNonEmptyList(
                                $inner_type
                            ),
                        ]);
                    }

                    return new Type\Union([
                        new Type\Atomic\TList(
                            $inner_type
                        ),
                    ]);
                }

                if ($array_arg_atomic_type instanceof Type\Atomic\TNonEmptyArray) {
                    return new Type\Union([
                        new Type\Atomic\TNonEmptyArray([
                            $generic_key_type,
                            $inner_type,
                        ]),
                    ]);
                }

                return new Type\Union([
                    new Type\Atomic\TArray([
                        $generic_key_type,
                        $inner_type,
                    ]),
                ]);
            } elseif ($function_call_arg->value instanceof PhpParser\Node\Scalar\String_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\Array_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\BinaryOp\Concat
            ) {
                $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                    $statements_source,
                    $function_call_arg->value
                );

                $call_map = CallMap::getCallMap();

                $mapping_return_type = null;

                $codebase = $statements_source->getCodebase();

                foreach ($mapping_function_ids as $mapping_function_id) {
                    $mapping_function_id = strtolower($mapping_function_id);

                    $mapping_function_id_parts = explode('&', $mapping_function_id);

                    $part_match_found = false;

                    foreach ($mapping_function_id_parts as $mapping_function_id_part) {
                        if (isset($call_map[$mapping_function_id_part][0])) {
                            if ($call_map[$mapping_function_id_part][0]) {
                                $mapped_function_return =
                                    Type::parseString($call_map[$mapping_function_id_part][0]);

                                if ($mapping_return_type) {
                                    $mapping_return_type = Type::combineUnionTypes(
                                        $mapping_return_type,
                                        $mapped_function_return
                                    );
                                } else {
                                    $mapping_return_type = $mapped_function_return;
                                }

                                $part_match_found = true;
                            }
                        } else {
                            if (strpos($mapping_function_id_part, '::') !== false) {
                                list($callable_fq_class_name) = explode('::', $mapping_function_id_part);

                                if (in_array($callable_fq_class_name, ['self', 'static', 'parent'], true)) {
                                    continue;
                                }

                                if (!$codebase->classlikes->classExists($callable_fq_class_name)) {
                                    continue;
                                }

                                if (!$codebase->methods->methodExists(
                                    $mapping_function_id_part,
                                    $context->calling_method_id,
                                    $codebase->collect_references
                                        ? new CodeLocation(
                                            $statements_source,
                                            $function_call_arg->value
                                        ) : null,
                                    null,
                                    $statements_source->getFilePath()
                                )) {
                                    continue;
                                }

                                $part_match_found = true;

                                $self_class = 'self';

                                $return_type = $codebase->methods->getMethodReturnType(
                                    $mapping_function_id_part,
                                    $self_class
                                ) ?: Type::getMixed();

                                $return_type = ExpressionAnalyzer::fleshOutType(
                                    $codebase,
                                    $return_type,
                                    $self_class,
                                    $self_class,
                                    $statements_source->getParentFQCLN()
                                );

                                if ($mapping_return_type) {
                                    $mapping_return_type = Type::combineUnionTypes(
                                        $mapping_return_type,
                                        $return_type
                                    );
                                } else {
                                    $mapping_return_type = $return_type;
                                }
                            } else {
                                if (!$codebase->functions->functionExists(
                                    $statements_source,
                                    $mapping_function_id_part
                                )
                                ) {
                                    $mapping_return_type = Type::getMixed();
                                    continue;
                                }

                                $part_match_found = true;

                                $function_storage = $codebase->functions->getStorage(
                                    $statements_source,
                                    $mapping_function_id_part
                                );

                                $return_type = $function_storage->return_type ?: Type::getMixed();

                                if ($mapping_return_type) {
                                    $mapping_return_type = Type::combineUnionTypes(
                                        $mapping_return_type,
                                        $return_type
                                    );
                                } else {
                                    $mapping_return_type = $return_type;
                                }
                            }
                        }
                    }

                    if ($part_match_found === false) {
                        $mapping_return_type = Type::getMixed();
                    }
                }

                if ($mapping_return_type) {
                    if ($array_arg_atomic_type instanceof Type\Atomic\ObjectLike && count($call_args) === 2) {
                        $atomic_type = new Type\Atomic\ObjectLike(
                            array_map(
                                /**
                                * @return Type\Union
                                */
                                function (Type\Union $_) use ($mapping_return_type) {
                                    return clone $mapping_return_type;
                                },
                                $array_arg_atomic_type->properties
                            )
                        );
                        $atomic_type->is_list = $array_arg_atomic_type->is_list;

                        return new Type\Union([$atomic_type]);
                    }

                    return new Type\Union([
                        count($call_args) === 2 && !($array_arg_type->is_list ?? false)
                            ? new Type\Atomic\TArray([
                                $generic_key_type,
                                $mapping_return_type,
                            ])
                            : new Type\Atomic\TList($mapping_return_type)
                    ]);
                }
            }
        }

        return count($call_args) === 2 && !($array_arg_type->is_list ?? false)
            ? new Type\Union([
                new Type\Atomic\TArray([
                    $array_arg_type->key ?? Type::getArrayKey(),
                    Type::getMixed(),
                ])
            ])
            : Type::getList();
    }
}
