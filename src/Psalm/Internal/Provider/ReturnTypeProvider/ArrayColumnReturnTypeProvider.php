<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

use function reset;

class ArrayColumnReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return ['array_column'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event) : Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer
            || \count($call_args) < 2
        ) {
            return Type::getMixed();
        }

        $row_shape = null;
        $input_array_not_empty = false;

        // calculate row shape
        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
            && $first_arg_type->isSingle()
            && $first_arg_type->hasArray()
        ) {
            $input_array = $first_arg_type->getAtomicTypes()['array'];
            $row_type = null;
            if ($input_array instanceof Type\Atomic\TKeyedArray) {
                $row_type = $input_array->getGenericArrayType()->type_params[1];
            } elseif ($input_array instanceof Type\Atomic\TArray) {
                $row_type = $input_array->type_params[1];
            } elseif ($input_array instanceof Type\Atomic\TList) {
                $row_type = $input_array->type_param;
            }

            if ($row_type && $row_type->isSingle()) {
                if ($row_type->hasArray()) {
                    $row_shape = $row_type->getAtomicTypes()['array'];
                } elseif ($row_type->hasObjectType()) {
                    $row_shape_union = GetObjectVarsReturnTypeProvider::getGetObjectVarsReturnType(
                        $row_type,
                        $statements_source,
                        $event->getContext(),
                        $event->getCodeLocation()
                    );
                    if ($row_shape_union->isSingle()) {
                        $row_shape_union_parts = $row_shape_union->getAtomicTypes();
                        $row_shape = reset($row_shape_union_parts);
                    }
                }
            }

            $input_array_not_empty = $input_array instanceof Type\Atomic\TNonEmptyList ||
                $input_array instanceof Type\Atomic\TNonEmptyArray ||
                $input_array instanceof Type\Atomic\TKeyedArray;
        }

        $value_column_name = null;
        // calculate value column name
        if (($second_arg_type = $statements_source->node_data->getType($call_args[1]->value))) {
            if ($second_arg_type->isSingleIntLiteral()) {
                $value_column_name = $second_arg_type->getSingleIntLiteral()->value;
            } elseif ($second_arg_type->isSingleStringLiteral()) {
                $value_column_name = $second_arg_type->getSingleStringLiteral()->value;
            }
        }

        $key_column_name = null;
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
            }
        }

        $result_key_type = Type::getArrayKey();
        $result_element_type = null;
        $have_at_least_one_res = false;
        // calculate results
        if ($row_shape instanceof Type\Atomic\TKeyedArray) {
            if ((null !== $value_column_name) && isset($row_shape->properties[$value_column_name])) {
                $result_element_type = $row_shape->properties[$value_column_name];
                // When the selected key is possibly_undefined, the resulting array can be empty
                if ($input_array_not_empty && $result_element_type->possibly_undefined !== true) {
                    $have_at_least_one_res = true;
                }
                //array_column skips undefined elements so resulting type is necesseraly defined
                $result_element_type->possibly_undefined = false;
            } else {
                $result_element_type = Type::getMixed();
            }

            if ((null !== $key_column_name) && isset($row_shape->properties[$key_column_name])) {
                $result_key_type = $row_shape->properties[$key_column_name];
            }
        }

        if (isset($call_args[2]) && (string)$third_arg_type !== 'null') {
            $type = $have_at_least_one_res ?
                new Type\Atomic\TNonEmptyArray([$result_key_type, $result_element_type ?? Type::getMixed()])
                : new Type\Atomic\TArray([$result_key_type, $result_element_type ?? Type::getMixed()]);
        } else {
            $type = $have_at_least_one_res ?
                new Type\Atomic\TNonEmptyList($result_element_type ?? Type::getMixed())
                : new Type\Atomic\TList($result_element_type ?? Type::getMixed());
        }

        return new Type\Union([$type]);
    }
}
