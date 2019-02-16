<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\Internal\Type\TypeCombination;
use Psalm\StatementsSource;

class ArrayMergeReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_merge'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        $inner_value_types = [];
        $inner_key_types = [];

        $generic_properties = [];

        foreach ($call_args as $call_arg) {
            if (!isset($call_arg->value->inferredType)) {
                return Type::getArray();
            }

            foreach ($call_arg->value->inferredType->getTypes() as $type_part) {
                if ($call_arg->unpack) {
                    if (!$type_part instanceof Type\Atomic\TArray) {
                        if ($type_part instanceof Type\Atomic\ObjectLike) {
                            $type_part_value_type = $type_part->getGenericValueType();
                        } else {
                            return Type::getArray();
                        }
                    } else {
                        $type_part_value_type = $type_part->type_params[1];
                    }

                    $unpacked_type_parts = [];

                    foreach ($type_part_value_type->getTypes() as $value_type_part) {
                        $unpacked_type_parts[] = $value_type_part;
                    }
                } else {
                    $unpacked_type_parts = [$type_part];
                }

                foreach ($unpacked_type_parts as $unpacked_type_part) {
                    if (!$unpacked_type_part instanceof Type\Atomic\TArray) {
                        if ($unpacked_type_part instanceof Type\Atomic\ObjectLike) {
                            if ($generic_properties !== null) {
                                $generic_properties = array_merge(
                                    $generic_properties,
                                    $unpacked_type_part->properties
                                );
                            }

                            $unpacked_type_part = $unpacked_type_part->getGenericArrayType();
                        } else {
                            if ($unpacked_type_part instanceof Type\Atomic\TMixed
                                && $unpacked_type_part->from_loop_isset
                            ) {
                                $unpacked_type_part = new Type\Atomic\TArray([
                                    Type::getArrayKey(),
                                    Type::getMixed(true)
                                ]);
                            } else {
                                return Type::getArray();
                            }
                        }
                    } elseif (!$unpacked_type_part->type_params[0]->isEmpty()) {
                        $generic_properties = null;
                    }

                    if ($unpacked_type_part->type_params[1]->isEmpty()) {
                        continue;
                    }

                    $inner_key_types = array_merge(
                        $inner_key_types,
                        array_values($unpacked_type_part->type_params[0]->getTypes())
                    );
                    $inner_value_types = array_merge(
                        $inner_value_types,
                        array_values($unpacked_type_part->type_params[1]->getTypes())
                    );
                }
            }
        }

        if ($generic_properties) {
            return new Type\Union([
                new Type\Atomic\ObjectLike($generic_properties),
            ]);
        }

        if ($inner_value_types) {
            return new Type\Union([
                new Type\Atomic\TArray([
                    TypeCombination::combineTypes($inner_key_types, null, true),
                    TypeCombination::combineTypes($inner_value_types, null, true),
                ]),
            ]);
        }

        return Type::getArray();
    }
}
