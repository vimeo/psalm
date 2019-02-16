<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Internal\Codebase\CallMap;

class ArrayColumnReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_column'];
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
        $row_shape = null;

        // calculate row shape
        if (isset($call_args[0]->value->inferredType)
            && $call_args[0]->value->inferredType->isSingle()
            && $call_args[0]->value->inferredType->hasArray()
        ) {
            $input_array = $call_args[0]->value->inferredType->getTypes()['array'];
            if ($input_array instanceof Type\Atomic\ObjectLike) {
                $row_type = $input_array->getGenericArrayType()->type_params[1];
                if ($row_type->isSingle() && $row_type->hasArray()) {
                    $row_shape = $row_type->getTypes()['array'];
                }
            } elseif ($input_array instanceof Type\Atomic\TArray) {
                $row_type = $input_array->type_params[1];
                if ($row_type->isSingle() && $row_type->hasArray()) {
                    $row_shape = $row_type->getTypes()['array'];
                }
            }
        }

        $value_column_name = null;
        // calculate value column name
        if (isset($call_args[1]->value->inferredType)) {
            $value_column_name_arg= $call_args[1]->value->inferredType;
            if ($value_column_name_arg->isSingleIntLiteral()) {
                $value_column_name = $value_column_name_arg->getSingleIntLiteral()->value;
            } elseif ($value_column_name_arg->isSingleStringLiteral()) {
                $value_column_name = $value_column_name_arg->getSingleStringLiteral()->value;
            }
        }

        $key_column_name = null;
        // calculate key column name
        if (isset($call_args[2]->value->inferredType)) {
            $key_column_name_arg = $call_args[2]->value->inferredType;
            if ($key_column_name_arg->isSingleIntLiteral()) {
                $key_column_name = $key_column_name_arg->getSingleIntLiteral()->value;
            } elseif ($key_column_name_arg->isSingleStringLiteral()) {
                $key_column_name = $key_column_name_arg->getSingleStringLiteral()->value;
            }
        }

        $result_key_type = Type::getArrayKey();
        $result_element_type = null;
        // calculate results
        if ($row_shape instanceof Type\Atomic\ObjectLike) {
            if ((null !== $value_column_name) && isset($row_shape->properties[$value_column_name])) {
                $result_element_type = $row_shape->properties[$value_column_name];
            } else {
                $result_element_type = Type::getMixed();
            }

            if ((null !== $key_column_name) && isset($row_shape->properties[$key_column_name])) {
                $result_key_type = $row_shape->properties[$key_column_name];
            }
        }

        if ($result_element_type) {
            return new Type\Union([
                new Type\Atomic\TArray([
                    $result_key_type,
                    $result_element_type
                ])
            ]);
        }

        return CallMap::getReturnTypeFromCallMap($function_id);
    }
}
