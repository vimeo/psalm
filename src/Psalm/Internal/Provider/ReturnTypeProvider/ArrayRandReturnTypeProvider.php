<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

/**
 * @internal
 */
final class ArrayRandReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_rand'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;
        $second_arg = $call_args[1]->value ?? null;

        $key_type = $first_arg
            && ($first_arg_type = $statements_source->node_data->getType($first_arg))
        ? $first_arg_type->getArrayKeyTypes()
        : null;

        if (!$key_type) {
            return Type::getMixed();
        }

        $key_type = Type::combineUnionTypeArray($key_type, $statements_source->getCodebase());

        if (!$second_arg) {
            return $key_type;
        }

        $second_arg_type = $statements_source->node_data->getType($second_arg);
        if ($second_arg_type
            && $second_arg_type->isSingleIntLiteral()
            && $second_arg_type->getSingleIntLiteral()->value === 1
        ) {
            return $key_type;
        }

        $arr_type = Type::getList($key_type);

        if ($second_arg_type && $second_arg_type->isSingleIntLiteral()) {
            return $arr_type;
        }

        return Type::combineUnionTypes($key_type, $arr_type);
    }
}
