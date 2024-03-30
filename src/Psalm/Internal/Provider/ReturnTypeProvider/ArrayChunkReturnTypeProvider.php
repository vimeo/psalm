<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class ArrayChunkReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_chunk'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        $statements_source = $event->getStatementsSource();
        if (count($call_args) >= 2
            && ($array_arg_type = $statements_source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $array_arg_type->isArray()
        ) {
            $codebase = $statements_source->getCodebase();
            $preserve_keys = isset($call_args[2])
                && ($preserve_keys_arg_type = $statements_source->getNodeTypeProvider()->getType($call_args[2]->value))
                && (string) $preserve_keys_arg_type !== 'false';

            return Type::getList(
                new Union([
                    $preserve_keys
                        ? new TNonEmptyArray([
                            Type::combineUnionTypeArray($array_arg_type->getArrayKeyTypes(), $codebase),
                            Type::combineUnionTypeArray($array_arg_type->getArrayValueTypes(), $codebase),
                        ])
                        : Type::getNonEmptyListAtomic(Type::combineUnionTypeArray($array_arg_type->getArrayValueTypes(), $codebase)),
                ]),
            );
        }

        return new Union([Type::getListAtomic(Type::getArray())]);
    }
}
