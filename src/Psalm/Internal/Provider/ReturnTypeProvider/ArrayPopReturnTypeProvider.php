<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

/**
 * @internal
 */
final class ArrayPopReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_pop', 'array_shift'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;

        $first_arg_is_array = $first_arg
            && ($first_arg_type = $statements_source->node_data->getType($first_arg))
            && $first_arg_type->hasArray()
            && !$first_arg_type->hasMixed();

        if (!$first_arg_is_array) {
            return Type::getMixed();
        }

        $nullable = false;

        $value_types = [];
        foreach ($first_arg_type->getArrays() as $first_arg_array) {
            if ($first_arg_array instanceof TArray) {
                $value_types []= $first_arg_array->type_params[1];

                if (!$first_arg_array instanceof TNonEmptyArray) {
                    $nullable = true;
                }
            } elseif ($first_arg_array instanceof TKeyedArray) {
                // special case where we know the type of the first element
                if ($function_id === 'array_shift' && $first_arg_array->is_list && isset($first_arg_array->properties[0])) {
                    $value_type = $first_arg_array->properties[0];
                    if ($value_type->possibly_undefined) {
                        $value_type = $value_type->setPossiblyUndefined(false);
                        $nullable = true;
                    }
                } else {
                    $value_type = $first_arg_array->getGenericValueType();

                    if (!$first_arg_array->isNonEmpty()) {
                        $nullable = true;
                    }
                }
                $value_types []= $value_type;
            }
        }


        $codebase = $statements_source->getCodebase();
        $ignore_nullable_issues = false;

        if ($nullable) {
            $value_types []= Type::getNull();
            $ignore_nullable_issues = $codebase->config->ignore_internal_nullable_issues;
        }

        $value_type = Type::combineUnionTypeArray($value_types, $codebase);
        $value_type->ignore_nullable_issues = $ignore_nullable_issues;

        return $value_type;
    }
}
