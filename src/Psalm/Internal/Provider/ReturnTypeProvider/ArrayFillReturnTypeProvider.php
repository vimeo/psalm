<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Union;

/**
 * @internal
 */
class ArrayFillReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_fill'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }
        $codebase = $statements_source->getCodebase();

        $first_arg_type = isset($call_args[0]) ? $statements_source->node_data->getType($call_args[0]->value) : null;
        $second_arg_type = isset($call_args[1]) ? $statements_source->node_data->getType($call_args[1]->value) : null;
        $third_arg_type = isset($call_args[2]) ? $statements_source->node_data->getType($call_args[2]->value) : null;

        $value_type_from_third_arg = $third_arg_type ? $third_arg_type : Type::getMixed();

        if ($first_arg_type && $second_arg_type && $third_arg_type
            && $first_arg_type->isSingleIntLiteral()
            && $second_arg_type->isSingleIntLiteral()
        ) {
            $first_arg_type = $first_arg_type->getSingleIntLiteral()->value;
            $second_arg_type = $second_arg_type->getSingleIntLiteral()->value;
            $is_list = $first_arg_type === 0;
            if ($second_arg_type < 0) {
                if ($codebase->analysis_php_version_id < 8_00_00) {
                    return Type::getFalse();
                }
                return Type::getNever();
            }
            $result = [];
            if ($first_arg_type < 0 && $codebase->analysis_php_version_id < 8_00_00) {
                $result[$first_arg_type] = $third_arg_type;
                $first_arg_type = 0;
                $second_arg_type--;
            }
            while ($second_arg_type > 0) {
                $result[$first_arg_type++] = $third_arg_type;
                $second_arg_type--;
            }
            if (!$result) {
                return Type::getEmptyArray();
            }
            return new Union([new TKeyedArray(
                $result,
                null,
                null,
                $is_list,
            )]);
        }
        if ($first_arg_type
            && $first_arg_type->isSingleIntLiteral()
            && $first_arg_type->getSingleIntLiteral()->value === 0
        ) {
            if ($second_arg_type
                && self::isPositiveNumericType($second_arg_type)
            ) {
                return Type::getNonEmptyList(
                    $value_type_from_third_arg,
                );
            }

            return Type::getList(
                $value_type_from_third_arg,
            );
        }

        if ($second_arg_type
            && self::isPositiveNumericType($second_arg_type)
        ) {
            if ($first_arg_type
                && $first_arg_type->isSingleIntLiteral()
                && $second_arg_type->isSingleIntLiteral()
            ) {
                return new Union([
                    new TNonEmptyArray([
                        new Union([new TIntRange(
                            $first_arg_type->getSingleIntLiteral()->value,
                            $second_arg_type->getSingleIntLiteral()->value,
                        )]),
                        $value_type_from_third_arg,
                    ]),
                ]);
            }

            return new Union([
                new TNonEmptyArray([
                    Type::getInt(),
                    $value_type_from_third_arg,
                ]),
            ]);
        }

        return new Union([
            new TArray([
                Type::getInt(),
                $value_type_from_third_arg,
            ]),
        ]);
    }

    private static function isPositiveNumericType(Union $arg): bool
    {
        if ($arg->isSingle()) {
            foreach ($arg->getRangeInts() as $range_int) {
                if ($range_int->isPositive()) {
                    return true;
                }
            }
        }

        return $arg->isSingleIntLiteral() && $arg->getSingleIntLiteral()->value > 0;
    }
}
