<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class CountReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['count', 'sizeof'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (!$call_args) {
            return null;
        }

        $first_arg_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[0]->value);
        if (!$first_arg_type) {
            return null;
        }

        $atomic_types = $first_arg_type->getAtomicTypes();
        if (count($atomic_types) !== 1 || !isset($atomic_types['array'])) {
            return null;
        }

        $array_type = $atomic_types['array'];

        if ($array_type instanceof TNonEmptyArray) {
            return new Union([
                $array_type->count !== null
                    ? new TLiteralInt($array_type->count)
                    : new TIntRange(1, null),
            ]);
        }

        if ($array_type instanceof TKeyedArray) {
            if ($array_type->is_callable) {
                return Type::getInt(false, 2);
            }
            $min = $array_type->getMinCount();
            $max = $array_type->getMaxCount();

            if ($min === $max) {
                return new Union([new TLiteralInt($max)]);
            }
            return Type::getIntRange($min, $max);
        }

        if ($array_type instanceof TArray && $array_type->isEmptyArray()) {
            return Type::getInt(false, 0);
        }

        return new Union([new TIntRange(0, null)]);
    }
}
