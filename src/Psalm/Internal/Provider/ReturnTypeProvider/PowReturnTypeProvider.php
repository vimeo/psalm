<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Union;

use function array_values;
use function count;
use function is_int;

/**
 * @internal
 */
final class PowReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    private const MAX_COMBINATIONS = 64;

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

        $source = $event->getStatementsSource();
        $first_atomic_types = self::getAtomicTypes($source->getNodeTypeProvider()->getType($call_args[0]->value));
        $second_atomic_types = self::getAtomicTypes($source->getNodeTypeProvider()->getType($call_args[1]->value));

        // the result of a union is the union of the results of each of its types
        if (count($first_atomic_types) * count($second_atomic_types) > self::MAX_COMBINATIONS) {
            $first_atomic_types = [null];
            $second_atomic_types = [null];
        }

        $result_types = [];

        foreach ($first_atomic_types as $first_atomic_type) {
            foreach ($second_atomic_types as $second_atomic_type) {
                $result_types[] = self::getResultType($first_atomic_type, $second_atomic_type);
            }
        }

        return Type::combineUnionTypeArray($result_types, $source->getCodebase());
    }

    /**
     * @return non-empty-list<?Atomic> null for an unknown type
     * @psalm-pure
     */
    private static function getAtomicTypes(?Union $type): array
    {
        return $type === null || $type->isUnionEmpty() ? [null] : array_values($type->getAtomicTypes());
    }

    /**
     * @psalm-pure
     */
    private static function getResultType(?Atomic $first_atomic_type, ?Atomic $second_atomic_type): Union
    {
        $first_arg_literal = null;
        $first_arg_is_int = false;
        $first_arg_is_float = false;
        if ($first_atomic_type !== null) {
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
        $second_arg_is_positive = false;
        if ($second_atomic_type !== null) {
            if ($second_atomic_type instanceof TInt) {
                $second_arg_is_int = true;
                if ($second_atomic_type instanceof TLiteralInt) {
                    $second_arg_is_negative = $second_atomic_type->value < 0;
                    $second_arg_can_be_negative = $second_arg_is_negative;
                    $second_arg_is_positive = $second_atomic_type->value > 0;
                } elseif ($second_atomic_type instanceof TIntRange) {
                    $second_arg_is_negative = $second_atomic_type->isNegative();
                    $second_arg_can_be_negative = !$second_atomic_type->isPositiveOrZero();
                    $second_arg_is_positive = $second_atomic_type->isPositive();
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

        if ($second_arg_literal === 0) {
            if ($first_arg_is_int) {
                return Type::getInt(true, 1);
            }
            if ($first_arg_is_float) {
                return Type::getFloat(1.0);
            }
        }
        if ($first_arg_literal === 0 && !$second_arg_can_be_negative) {
            // 0 ** 0 is 1, so the result is only 0 when the exponent is known to be positive
            if ($second_arg_is_int && $second_arg_is_positive) {
                return Type::getInt(true, 0);
            }
            if ($second_arg_literal !== null && $second_arg_literal > 0) {
                return Type::getFloat(0.0);
            }
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
