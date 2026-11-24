<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Union;

use function count;
use function is_int;

/**
 * @internal
 */
final class PowReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['pow'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();

        if (count($call_args) !== 2) {
            return null;
        }

        $first_arg = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[0]->value);
        $second_arg = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[1]->value);

        $first_arg_literal = null;
        $first_arg_is_int = false;
        $first_arg_is_float = false;
        if ($first_arg !== null && $first_arg->isSingle()) {
            $first_atomic_type = $first_arg->getSingleAtomic();
            if ($first_atomic_type instanceof TInt) {
                $first_arg_is_int = true;
            } elseif ($first_atomic_type instanceof TFloat) {
                $first_arg_is_float = true;
            }
            if ($first_atomic_type instanceof TLiteralInt
                || $first_atomic_type instanceof TLiteralFloat
            ) {
                $first_arg_literal = $first_atomic_type->value;
            }
        }

        $second_arg_literal = null;
        $second_arg_is_int = false;
        $second_arg_is_float = false;
        $second_arg_is_negative = false;
        $second_arg_can_be_negative = false;
        if ($second_arg !== null && $second_arg->isSingle()) {
            $second_atomic_type = $second_arg->getSingleAtomic();
            if ($second_atomic_type instanceof TInt) {
                $second_arg_is_int = true;
                if ($second_atomic_type instanceof TLiteralInt) {
                    $second_arg_is_negative = $second_atomic_type->value < 0;
                    $second_arg_can_be_negative = $second_arg_is_negative;
                } elseif ($second_atomic_type instanceof TIntRange) {
                    $second_arg_is_negative = $second_atomic_type->isNegative();
                    $second_arg_can_be_negative = !$second_atomic_type->isPositiveOrZero();
                } else {
                    $second_arg_can_be_negative = true;
                }
            } elseif ($second_atomic_type instanceof TFloat) {
                $second_arg_is_float = true;
            }
            if ($second_atomic_type instanceof TLiteralInt
                || $second_atomic_type instanceof TLiteralFloat
            ) {
                $second_arg_literal = $second_atomic_type->value;
            }
        }

        if ($first_arg_literal === 0 && !$second_arg_can_be_negative) {
            return Type::getInt(true, 0);
        }
        if ($second_arg_literal === 0) {
            return Type::getInt(true, 1);
        }
        if ($first_arg_literal !== 0 && $first_arg_literal !== null && $second_arg_literal !== null) {
            $result = $first_arg_literal ** $second_arg_literal;

            return is_int($result) ? Type::getInt(true, $result) : Type::getFloat($result);
        }
        if ($first_arg_is_int && $second_arg_is_int) {
            if ($second_arg_is_negative) {
                return Type::getFloat();
            }

            return $second_arg_can_be_negative ? new Union([new TInt(), new TFloat()]) : Type::getInt();
        }
        if ($first_arg_is_float || $second_arg_is_float) {
            return Type::getFloat();
        }

        return new Union([new TInt(), new TFloat()]);
    }
}
