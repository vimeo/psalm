<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class ArrayFillKeysReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_fill_keys'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }
        if (count($call_args) !== 2) {
            return Type::getNever();
        }

        $first_arg_type = isset($call_args[0]) ? $statements_source->node_data->getType($call_args[0]->value) : null;
        $second_arg_type = isset($call_args[1]) ? $statements_source->node_data->getType($call_args[1]->value) : null;

        if ($first_arg_type
            && $first_arg_type->isArray()
            && $second_arg_type
        ) {
            $array = $first_arg_type->getArray();
            if ($array instanceof TArray && $array->isEmptyArray()) {
                return $first_arg_type;
            } elseif ($array instanceof TKeyedArray && !$array->fallback_params) {
                $is_list = $array->is_list;
                $array = $array->properties;
            } else {
                return null;
            }
            $result = [];
            $prev_key = -1;
            $had_possibly_undefined = false;
            foreach ($array as $key_k) {
                if ($had_possibly_undefined && !$key_k->possibly_undefined) {
                    $is_list = false;
                }
                $had_possibly_undefined = $had_possibly_undefined || $key_k->possibly_undefined;

                if ($key_k->isSingleIntLiteral()) {
                    $key = $key_k->getSingleIntLiteral()->value;
                    if ($prev_key !== $key-1) {
                        $is_list = false;
                    }
                    $prev_key = $key;
                } elseif ($key_k->isSingleStringLiteral()) {
                    $key = $key_k->getSingleStringLiteral()->value;
                    $is_list = false;
                } else {
                    return null;
                }
                $result[$key] = $second_arg_type->setPossiblyUndefined(
                    $key_k->possibly_undefined,
                );
            }
            return new Union([new TKeyedArray(
                $result,
                null,
                null,
                $is_list,
            )]);
        }

        return null;
    }
}
