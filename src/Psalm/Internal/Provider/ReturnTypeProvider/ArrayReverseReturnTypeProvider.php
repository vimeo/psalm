<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function array_reverse;

/**
 * @internal
 */
final class ArrayReverseReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_reverse'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;
        $first_arg_type = null;

        $first_arg_array = $first_arg
            && ($first_arg_type = $statements_source->node_data->getType($first_arg))
            && $first_arg_type->hasType('array')
            && $first_arg_type->isArray()
            && ($array_atomic_type = $first_arg_type->getArray())
            && ($array_atomic_type instanceof TArray
                || $array_atomic_type instanceof TKeyedArray)
        ? $array_atomic_type
        : null;

        if (!$first_arg_array || !$first_arg_type) {
            return Type::getArray();
        }

        if ($first_arg_array instanceof TArray) {
            return $first_arg_type;
        }

        if ($first_arg_array->is_list) {
            $second_arg = $call_args[1]->value ?? null;

            if (!$second_arg
                || (($second_arg_type = $statements_source->node_data->getType($second_arg))
                    && $second_arg_type->isFalse()
                )
            ) {
                return $first_arg_array->fallback_params
                    ? ($first_arg_array->isNonEmpty()
                        ? Type::getNonEmptyList($first_arg_array->getGenericValueType())
                        : Type::getList($first_arg_array->getGenericValueType())
                    )
                    : new Union([$first_arg_array->setProperties(array_reverse($first_arg_array->properties))]);
            }

            return new Union([new TKeyedArray(
                $first_arg_array->properties,
                null,
                $first_arg_array->fallback_params,
                false,
            )]);
        }

        return new Union([$first_arg_array->getGenericArrayType()]);
    }
}
