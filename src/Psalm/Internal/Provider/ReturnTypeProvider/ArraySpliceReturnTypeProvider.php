<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
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

        if (!$first_arg
            || !($first_arg_type = $statements_source->node_data->getType($first_arg))
            || !$first_arg_type->hasArray()
        ) {
            return Type::getArray();
        }

        // TODO improve this logic
        $results = [];
        foreach ($first_arg_type->getArrays() as $array_type) {
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
            $results []= $array_type;
        }

        return new Union($results);
    }
}
