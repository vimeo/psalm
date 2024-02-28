<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

/**
 * @internal
 */
final class ArraySpliceReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_splice'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;

        $array_type = $first_arg
            && ($first_arg_type = $statements_source->node_data->getType($first_arg))
            && $first_arg_type->hasType('array')
            && ($array_atomic_type = $first_arg_type->getArray())
            && ($array_atomic_type instanceof TArray
                || $array_atomic_type instanceof TKeyedArray)
        ? $array_atomic_type
        : null;

        if (!$array_type) {
            return Type::getArray();
        }

        if ($array_type instanceof TKeyedArray) {
            $array_type = $array_type->getGenericArrayType();
        }

        if (!$array_type->type_params[0]->hasString()) {
            if ($array_type->type_params[1]->isString()) {
                $array_type = Type::getListAtomic(Type::getString());
            } elseif ($array_type->type_params[1]->isInt()) {
                $array_type = Type::getListAtomic(Type::getInt());
            } else {
                $array_type = Type::getListAtomic(Type::getMixed());
            }
        }

        return new Union([$array_type]);
    }
}
