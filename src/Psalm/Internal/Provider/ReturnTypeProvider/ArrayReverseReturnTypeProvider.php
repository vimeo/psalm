<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function array_reverse;

/**
 * @internal
 */
class ArrayReverseReturnTypeProvider implements FunctionReturnTypeProviderInterface
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

        if (!$first_arg) {
            return Type::getArray();
        }
        if (!($first_arg_type = $statements_source->node_data->getType($first_arg))) {
            return Type::getArray();
        }

        $result = [];
        foreach ($first_arg_type->getArrays() as $first_arg_array) {
            if ($first_arg_array instanceof TClassStringMap) {
                continue; // TODO
            }
            if ($first_arg_array instanceof TArray) {
                $result []= $first_arg_type;
                continue;
            }

            if ($first_arg_array->is_list) {
                $second_arg = $call_args[1]->value ?? null;

                if (!$second_arg
                    || (($second_arg_type = $statements_source->node_data->getType($second_arg))
                        && $second_arg_type->isFalse()
                    )
                ) {
                    $result []= $first_arg_array->fallback_params
                    ? ($first_arg_array->isNonEmpty()
                        ? Type::getNonEmptyListAtomic($first_arg_array->getGenericValueType())
                        : Type::getListAtomic($first_arg_array->getGenericValueType())
                        )
                        : $first_arg_array->setProperties(array_reverse($first_arg_array->properties));
                    continue;
                }

                $result []= new TKeyedArray(
                    $first_arg_array->properties,
                    null,
                    $first_arg_array->fallback_params,
                    false,
                );
                continue;
            }

            $result []= $first_arg_array->getGenericArrayType();
        }
        return $result ? new Union($result) : Type::getArray();
    }
}
