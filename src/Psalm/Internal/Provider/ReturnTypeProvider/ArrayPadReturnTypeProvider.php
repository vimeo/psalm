<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class ArrayPadReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_pad'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $type_provider = $statements_source->getNodeTypeProvider();

        if (count($call_args) >= 3
            && ($array_arg_type = $type_provider->getType($call_args[0]->value))
            && ($size_arg_type = $type_provider->getType($call_args[1]->value))
            && ($value_arg_type = $type_provider->getType($call_args[2]->value))
            && $array_arg_type->isArray()
        ) {
            $codebase = $statements_source->getCodebase();
            $key_type = Type::combineUnionTypeArray([...$array_arg_type->getArrayKeyTypes(), Type::getInt()], $codebase);
            $value_type = Type::combineUnionTypeArray([...$array_arg_type->getArrayValueTypes(), $value_arg_type], $codebase);
            $can_return_empty = (
                !$size_arg_type->isSingleIntLiteral()
                || $size_arg_type->getSingleIntLiteral()->value === 0
            );

            return new Union([
                $array_arg_type->isList()
                    ? (
                        $can_return_empty
                            ? Type::getListAtomic($value_type)
                            : Type::getNonEmptyListAtomic($value_type)
                    )
                    : (
                        $can_return_empty
                            ? new TArray([$key_type, $value_type])
                            : new TNonEmptyArray([$key_type, $value_type])
                    ),
            ]);
        }

        return Type::getArray();
    }
}
