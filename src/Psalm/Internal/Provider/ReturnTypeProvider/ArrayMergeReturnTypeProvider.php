<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

use function array_merge;
use function array_values;
use function count;
use function is_string;
use function max;

/**
 * @internal
 */
final class ArrayMergeReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_merge', 'array_replace'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer
            || !$call_args
        ) {
            return Type::getMixed();
        }

        $is_replace = $event->getFunctionId() === 'array_replace';

        $inner_value_types = [];
        $inner_key_types = [];

        $codebase = $statements_source->getCodebase();

        $generic_properties = [];
        $class_strings = [];
        $all_keyed_arrays = true;
        $all_int_offsets = true;
        $all_nonempty_lists = true;
        $any_nonempty = false;
        $all_empty = true;

        $max_keyed_array_size = 0;

        foreach ($call_args as $call_arg) {
            if (!($call_arg_type = $statements_source->node_data->getType($call_arg->value))) {
                return Type::getArray();
            }

            foreach ($call_arg_type->getAtomicTypes() as $type_part) {
                if ($type_part instanceof TList) {
                    $type_part = $type_part->getKeyedArray();
                }
                $unpacking_indefinite_number_of_args = false;
                $unpacking_possibly_empty = false;
                if ($call_arg->unpack) {
                    if ($type_part instanceof TKeyedArray) {
                        if (!$type_part->fallback_params
                            && $type_part->getMinCount() === $type_part->getMaxCount()
                        ) {
                            $unpacked_type_parts = [];
                            foreach ($type_part->properties as $t) {
                                $unpacked_type_parts = array_merge(
                                    $unpacked_type_parts,
                                    $t->getAtomicTypes(),
                                );
                            }
                        } else {
                            $unpacked_type_parts = $type_part
                                ->getGenericValueType()
                                ->getAtomicTypes();
                            $unpacking_indefinite_number_of_args = true;
                        }
                        $unpacking_possibly_empty = !$type_part->isNonEmpty();
                    } elseif ($type_part instanceof TArray) {
                        $unpacked_type_parts = $type_part->type_params[1];
                        $unpacking_indefinite_number_of_args = true;
                        $unpacking_possibly_empty = !$type_part instanceof TNonEmptyArray;
                        $unpacked_type_parts = $unpacked_type_parts->getAtomicTypes();
                    } else {
                        return Type::getArray();
                    }
                } else {
                    $unpacked_type_parts = [$type_part];
                }

                foreach ($unpacked_type_parts as $unpacked_type_part) {
                    if (($unpacked_type_part instanceof TFalse
                            && $call_arg_type->ignore_falsable_issues)
                        || ($unpacked_type_part instanceof TNull
                            && $call_arg_type->ignore_nullable_issues)
                    ) {
                        continue;
                    }

                    if ($unpacked_type_part instanceof TKeyedArray) {
                        $all_empty = false;

                        $max_keyed_array_size = max(
                            $max_keyed_array_size,
                            count($unpacked_type_part->properties),
                        );

                        $added_inner_values = false;
                        foreach ($unpacked_type_part->properties as $key => $type) {
                            if (!$type->possibly_undefined && !$unpacking_possibly_empty) {
                                $any_nonempty = true;
                            }
                            if (is_string($key)) {
                                $all_int_offsets = false;
                            } elseif (!$is_replace) {
                                if ($unpacking_indefinite_number_of_args || $type->possibly_undefined) {
                                    $added_inner_values = true;
                                    $inner_value_types = array_merge(
                                        $inner_value_types,
                                        array_values($type->getAtomicTypes()),
                                    );
                                } else {
                                    $generic_properties[] = $type;
                                }
                                continue;
                            }

                            if (isset($unpacked_type_part->class_strings[$key])) {
                                $class_strings[$key] = true;
                            }

                            if (!isset($generic_properties[$key]) || (
                                !$type->possibly_undefined
                                    && !$unpacking_possibly_empty
                            )) {
                                if ($unpacking_possibly_empty) {
                                    $type = $type->setPossiblyUndefined(true);
                                }
                                $generic_properties[$key] = $type;
                            } else {
                                $was_possibly_undefined = $generic_properties[$key]->possibly_undefined
                                    || $unpacking_possibly_empty;

                                $generic_properties[$key] = Type::combineUnionTypes(
                                    $generic_properties[$key],
                                    $type,
                                    $codebase,
                                    false,
                                    true,
                                    500,
                                    $was_possibly_undefined,
                                );
                            }
                        }

                        if (!$unpacked_type_part->is_list) {
                            $all_nonempty_lists = false;
                        }

                        if ($added_inner_values) {
                            $all_keyed_arrays = false;
                            $inner_key_types []= new TInt;
                        }

                        if ($unpacked_type_part->fallback_params !== null) {
                            $all_keyed_arrays = false;
                            $inner_value_types = array_merge(
                                $inner_value_types,
                                array_values($unpacked_type_part->fallback_params[1]->getAtomicTypes()),
                            );
                            $inner_key_types = array_merge(
                                $inner_key_types,
                                array_values($unpacked_type_part->fallback_params[0]->getAtomicTypes()),
                            );
                        }

                        continue;
                    }

                    if ($unpacked_type_part instanceof TMixed
                        && $unpacked_type_part->from_loop_isset
                    ) {
                        $unpacked_type_part = new TArray([
                            Type::getArrayKey(),
                            Type::getMixed(true),
                        ]);
                    }

                    if ($unpacked_type_part instanceof TArray) {
                        if ($unpacked_type_part->isEmptyArray()) {
                            continue;
                        }

                        foreach ($generic_properties as $key => $keyed_type) {
                            $generic_properties[$key] = Type::combineUnionTypes(
                                $keyed_type,
                                $unpacked_type_part->type_params[1],
                                $codebase,
                            );
                        }

                        $all_keyed_arrays = false;
                        $all_nonempty_lists = false;

                        if (!$unpacked_type_part->type_params[0]->isInt()) {
                            $all_int_offsets = false;
                        }

                        if ($unpacked_type_part instanceof TNonEmptyArray && !$unpacking_possibly_empty) {
                            $any_nonempty = true;
                        }
                    } else {
                        return Type::getArray();
                    }

                    $all_empty = false;

                    $inner_key_types = array_merge(
                        $inner_key_types,
                        array_values($unpacked_type_part->type_params[0]->getAtomicTypes()),
                    );
                    $inner_value_types = array_merge(
                        $inner_value_types,
                        array_values($unpacked_type_part->type_params[1]->getAtomicTypes()),
                    );
                }
            }
        }

        $inner_key_type = null;
        $inner_value_type = null;

        if ($inner_key_types) {
            $inner_key_type = TypeCombiner::combine($inner_key_types, $codebase, true);
        }

        if ($inner_value_types) {
            $inner_value_type = TypeCombiner::combine($inner_value_types, $codebase, true);
        }

        $generic_property_count = count($generic_properties);

        if ($generic_properties
            && $generic_property_count < 64
            && ($generic_property_count < $max_keyed_array_size * 2
                || $generic_property_count < 16)
        ) {
            $objectlike = new TKeyedArray(
                $generic_properties,
                $class_strings ?: null,
                $all_keyed_arrays || $inner_key_type === null || $inner_value_type === null
                    ? null
                    : [$inner_key_type, $inner_value_type],
                $all_nonempty_lists || $all_int_offsets,
            );

            return new Union([$objectlike]);
        }

        if ($all_empty) {
            return Type::getEmptyArray();
        }

        if ($inner_value_type) {
            if ($all_int_offsets) {
                if ($any_nonempty) {
                    return Type::getNonEmptyList(
                        $inner_value_type,
                    );
                }

                return Type::getList($inner_value_type);
            }

            $inner_key_type ??= Type::getArrayKey();

            if ($any_nonempty) {
                return new Union([
                    new TNonEmptyArray([
                        $inner_key_type,
                        $inner_value_type,
                    ]),
                ]);
            }

            return new Union([
                new TArray([
                    $inner_key_type,
                    $inner_value_type,
                ]),
            ]);
        }

        return Type::getArray();
    }
}
