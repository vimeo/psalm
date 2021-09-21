<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

class ArrayFillReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return ['array_fill'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event) : Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg_type = isset($call_args[0]) ? $statements_source->node_data->getType($call_args[0]->value) : null;
        $second_arg_type = isset($call_args[1]) ? $statements_source->node_data->getType($call_args[1]->value) : null;
        $third_arg_type = isset($call_args[2]) ? $statements_source->node_data->getType($call_args[2]->value) : null;

        $value_type_from_third_arg = $third_arg_type ? clone $third_arg_type : Type::getMixed();

        if ($first_arg_type
            && $first_arg_type->isSingleIntLiteral()
            && $first_arg_type->getSingleIntLiteral()->value === 0
        ) {
            if ($second_arg_type
                && self::isPositiveNumericType($second_arg_type)
            ) {
                return new Type\Union([
                    new Type\Atomic\TNonEmptyList(
                        $value_type_from_third_arg
                    )
                ]);
            }

            return new Type\Union([
                new Type\Atomic\TList(
                    $value_type_from_third_arg
                )
            ]);
        }

        if ($second_arg_type
            && self::isPositiveNumericType($second_arg_type)
        ) {
            return new Type\Union([
                new Type\Atomic\TNonEmptyArray([
                    Type::getInt(),
                    $value_type_from_third_arg,
                ])
            ]);
        }

        return new Type\Union([
            new Type\Atomic\TArray([
                Type::getInt(),
                $value_type_from_third_arg,
            ])
        ]);
    }

    private static function isPositiveNumericType(Type\Union $arg): bool
    {
        if ($arg->isSingle() && $arg->hasPositiveInt()) {
            return true;
        }

        return $arg->isSingleIntLiteral() && $arg->getSingleIntLiteral()->value > 0;
    }
}
