<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Type\ArrayType;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class ArrayFindKeyReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_find_key'];
    }

    /** The key it returns is one of the keys of the array, or null when no element matches. */
    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        $type_provider = $event->getStatementsSource()->getNodeTypeProvider();

        if (count($call_args) < 2
            || !($array_arg_type = $type_provider->getType($call_args[0]->value))
            || !$array_arg_type->isSingle()
            || !$array_arg_type->hasArray()
            || !($array_type = ArrayType::infer($array_arg_type->getArray()))
        ) {
            return null;
        }

        return Type::combineUnionTypes(
            $array_type->key,
            Type::getNull(),
            $event->getStatementsSource()->getCodebase(),
        );
    }
}
