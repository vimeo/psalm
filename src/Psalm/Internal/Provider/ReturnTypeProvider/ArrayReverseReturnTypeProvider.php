<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function array_reverse;
use function array_values;
use function count;

/**
 * @internal
 */
final class ArrayReverseReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_reverse'];
    }

    #[Override]
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
                if ($first_arg_array->fallback_params) {
                    return $first_arg_array->isNonEmpty()
                        ? Type::getNonEmptyList($first_arg_array->getGenericValueType())
                        : Type::getList($first_arg_array->getGenericValueType());
                }

                $reversed_array_items = [];
                $num_undefined = 0;
                $i = 0;
                foreach (array_reverse($first_arg_array->properties) as $array_item_type) {
                    $reversed_array_items[] = $array_item_type;
                    /** @var int<0,max> $j */
                    $j = $i - $num_undefined;
                    for (; $j < $i; ++$j) {
                        $reversed_array_items[$j] = TypeCombiner::combine([
                            ...array_values($reversed_array_items[$j]->getAtomicTypes()),
                            ...array_values($array_item_type->getAtomicTypes()),
                        ]);
                    }
                    if ($array_item_type->possibly_undefined) {
                        ++$num_undefined;
                    }
                    ++$i;
                }

                $max_len = count($reversed_array_items);
                /** @var int<0,max> $i */
                $i = $max_len - $num_undefined;
                for (; $i < $max_len; ++$i) {
                    $reversed_array_items[$i] = $reversed_array_items[$i]->setPossiblyUndefined(true);
                }

                return new Union([$first_arg_array->setProperties($reversed_array_items)]);
            }

            return new Union([TKeyedArray::make(
                $first_arg_array->properties,
                null,
                $first_arg_array->fallback_params,
                false,
            )]);
        }

        return new Union([$first_arg_array->getGenericArrayType()]);
    }
}
