<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\SourceAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
class ArrayColumnReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_column'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer
            || count($call_args) < 2
        ) {
            return Type::getMixed();
        }
        $context = $event->getContext();
        $code_location = $event->getCodeLocation();

        $value_column_name = null;
        $value_column_name_is_null = false;
        // calculate value column name
        if (($second_arg_type = $statements_source->node_data->getType($call_args[1]->value))) {
            if ($second_arg_type->isSingleIntLiteral()) {
                $value_column_name = $second_arg_type->getSingleIntLiteral()->value;
            } elseif ($second_arg_type->isSingleStringLiteral()) {
                $value_column_name = $second_arg_type->getSingleStringLiteral()->value;
            }
            $value_column_name_is_null = $second_arg_type->isNull();
        }

        $key_column_name = null;
        $key_column_name_is_null = false;
        $third_arg_type = null;
        // calculate key column name
        if (isset($call_args[2])) {
            $third_arg_type = $statements_source->node_data->getType($call_args[2]->value);

            if ($third_arg_type) {
                if ($third_arg_type->isSingleIntLiteral()) {
                    $key_column_name = $third_arg_type->getSingleIntLiteral()->value;
                } elseif ($third_arg_type->isSingleStringLiteral()) {
                    $key_column_name = $third_arg_type->getSingleStringLiteral()->value;
                }
                $key_column_name_is_null = $third_arg_type->isNull();
            }
        }


        $row_type = $row_shape = null;
        $input_array_not_empty = false;

        // calculate row shape
        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
            && $first_arg_type->isSingle()
            && $first_arg_type->hasArray()
        ) {
            $input_array = $first_arg_type->getArray();
            if ($input_array instanceof TKeyedArray && !$input_array->fallback_params
                && ($value_column_name !== null || $value_column_name_is_null)
                && !($third_arg_type && !$key_column_name)
            ) {
                $properties = [];
                $ok = true;
                $last_custom_key = -1;
                $is_list = true;
                $had_possibly_undefined = false;

                // This incorrectly assumes that the array is sorted, may be problematic
                // Will be fixed when order is enforced
                $key = -1;
                foreach ($input_array->properties as $property) {
                    $row_shape = self::getRowShape(
                        $property,
                        $statements_source,
                        $context,
                        $code_location,
                    );
                    if (!$row_shape) {
                        continue;
                    }
                    if (!$row_shape instanceof TKeyedArray) {
                        if ($row_shape instanceof TArray && $row_shape->isEmptyArray()) {
                            continue;
                        }
                        $ok = false;
                        break;
                    }

                    if ($value_column_name !== null) {
                        if (isset($row_shape->properties[$value_column_name])) {
                            $result_element_type = $row_shape->properties[$value_column_name];
                        } elseif ($row_shape->fallback_params) {
                            $ok = false;
                            break;
                        } else {
                            continue;
                        }
                    } else {
                        $result_element_type = $property;
                    }

                    if ($key_column_name !== null) {
                        if (isset($row_shape->properties[$key_column_name])) {
                            $result_key_type = $row_shape->properties[$key_column_name];
                            if ($result_key_type->isSingleIntLiteral()) {
                                $key = $result_key_type->getSingleIntLiteral()->value;
                                if ($is_list && $last_custom_key != $key-1) {
                                    $is_list = false;
                                }
                                $last_custom_key = $key;
                            } elseif ($result_key_type->isSingleStringLiteral()) {
                                $key = $result_key_type->getSingleStringLiteral()->value;
                                $is_list = false;
                            } else {
                                $ok = false;
                                break;
                            }
                        } else {
                            $ok = false;
                            break;
                        }
                    } else {
                        /** @psalm-suppress StringIncrement Actually always an int in this branch */
                        ++$key;
                    }

                    $properties[$key] = $result_element_type->setPossiblyUndefined(
                        $property->possibly_undefined,
                    );

                    if (!$property->possibly_undefined
                        && $had_possibly_undefined
                    ) {
                        $is_list = false;
                    }

                    $had_possibly_undefined = $had_possibly_undefined || $property->possibly_undefined;
                }
                if ($ok) {
                    if (!$properties) {
                        return Type::getEmptyArray();
                    }
                    return new Union([new TKeyedArray(
                        $properties,
                        null,
                        $input_array->fallback_params,
                        $is_list,
                    )]);
                }
            }

            if ($input_array instanceof TKeyedArray) {
                $row_type = $input_array->getGenericValueType();
            } elseif ($input_array instanceof TArray) {
                $row_type = $input_array->type_params[1];
            }

            $row_shape = self::getRowShape(
                $row_type,
                $statements_source,
                $context,
                $code_location,
            );

            $input_array_not_empty = $input_array instanceof TNonEmptyArray ||
                ($input_array instanceof TKeyedArray && $input_array->isNonEmpty());
        }


        $result_key_type = Type::getArrayKey();
        $result_element_type = null !== $row_type && $value_column_name_is_null ? $row_type : null;
        $have_at_least_one_res = false;
        // calculate results
        if ($row_shape instanceof TKeyedArray) {
            if ((null !== $value_column_name) && isset($row_shape->properties[$value_column_name])) {
                $result_element_type = $row_shape->properties[$value_column_name];
                // When the selected key is possibly_undefined, the resulting array can be empty
                if ($input_array_not_empty && $result_element_type->possibly_undefined !== true) {
                    $have_at_least_one_res = true;
                }
                //array_column skips undefined elements so resulting type is necessarily defined
                $result_element_type = $result_element_type->setPossiblyUndefined(false);
            } elseif (!$value_column_name_is_null) {
                $result_element_type = Type::getMixed();
            }

            if ((null !== $key_column_name) && isset($row_shape->properties[$key_column_name])) {
                $result_key_type = $row_shape->properties[$key_column_name];
            }
        }

        if ($third_arg_type && !$key_column_name_is_null) {
            $type = $have_at_least_one_res ?
                new TNonEmptyArray([$result_key_type, $result_element_type ?? Type::getMixed()])
                : new TArray([$result_key_type, $result_element_type ?? Type::getMixed()]);
        } else {
            $type = $have_at_least_one_res ?
                Type::getNonEmptyListAtomic($result_element_type ?? Type::getMixed())
                : Type::getListAtomic($result_element_type ?? Type::getMixed());
        }

        return new Union([$type]);
    }

    /**
     * @return TArray|TKeyedArray|TClassStringMap|null
     */
    private static function getRowShape(
        ?Union $row_type,
        SourceAnalyzer $statements_source,
        Context $context,
        CodeLocation $code_location
    ): ?Atomic {
        if ($row_type && $row_type->isSingle()) {
            if ($row_type->hasArray()) {
                return $row_type->getArray();
            } elseif ($row_type->hasObjectType()) {
                return GetObjectVarsReturnTypeProvider::getGetObjectVarsReturnType(
                    $row_type,
                    $statements_source,
                    $context,
                    $code_location,
                );
            }
        }
        return null;
    }
}
