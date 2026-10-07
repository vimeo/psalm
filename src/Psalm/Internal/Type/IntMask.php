<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use PhpParser\Node\Expr\BinaryOp;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Union;

use function array_values;
use function count;

use const PHP_INT_SIZE;

/**
 * The bit sets of int-mask types, tracked by Union::$int_mask_bits.
 *
 * @psalm-immutable
 * @internal
 */
final class IntMask
{
    /**
     * Above this many pairs of literal operands, a bitwise operation on a mask is not computed pair
     * by pair: two capability-like masks of 7 bits would make 16384 pairs.
     */
    private const MAX_LITERAL_PAIRS = 256;

    /**
     * Above this many bits, the sets of a mask's bits are not listed one by one.
     */
    private const MAX_LITERAL_BITS = 8;

    /**
     * Whether an atomic type may be an int that is not known literally.
     *
     * @psalm-pure
     */
    public static function isNonLiteralInt(Atomic $atomic): bool
    {
        return ($atomic instanceof TInt && !$atomic instanceof TLiteralInt)
            || $atomic instanceof TArrayKey
            || $atomic instanceof TNumeric
            || $atomic instanceof TScalar
            || $atomic instanceof TMixed;
    }

    /**
     * Whether every int a type may hold only has the given bits.
     *
     * @psalm-pure
     */
    public static function fits(Union $type, int $bits): bool
    {
        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TLiteralInt) {
                if (($atomic->value & ~$bits) !== 0) {
                    return false;
                }
            } elseif (self::isNonLiteralInt($atomic)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The bits of a mask expanded to literals, or null when it could not be.
     *
     * @param list<Atomic> $expanded
     * @psalm-pure
     */
    public static function getExpandedBits(array $expanded): ?int
    {
        $bits = 0;

        foreach ($expanded as $atomic) {
            if (!$atomic instanceof TLiteralInt) {
                return null;
            }

            $bits |= $atomic->value;
        }

        return $bits;
    }

    /**
     * The bits of a combination of two types: a mask stays a mask when the other type only adds
     * values of its bits, or other masks.
     *
     * @psalm-pure
     */
    public static function combineBits(Union $type_1, Union $type_2): ?int
    {
        $bits_1 = $type_1->int_mask_bits;
        $bits_2 = $type_2->int_mask_bits;

        if ($bits_1 === null) {
            return $bits_2 !== null && self::fits($type_1, $bits_2) ? $bits_2 : null;
        }

        if ($bits_2 === null) {
            return self::fits($type_2, $bits_1) ? $bits_1 : null;
        }

        return $bits_1 | $bits_2;
    }

    /**
     * The bits of the result of `&`, `|` or `^` on a mask, or null when the result is not a mask.
     *
     * `$mask & $x` only has bits of the mask, whatever $x is: `$mask & FLAG` narrows the mask to
     * FLAG, and `$mask & ~FLAG` to the mask's other bits. `|` and `^` keep a mask only when the other
     * operand has known bits.
     *
     * @psalm-pure
     */
    public static function getBitwiseOpBits(BinaryOp $stmt, Union $left_type, Union $right_type): ?int
    {
        if (!$stmt instanceof BinaryOp\BitwiseAnd
            && !$stmt instanceof BinaryOp\BitwiseOr
            && !$stmt instanceof BinaryOp\BitwiseXor
        ) {
            return null;
        }

        if (($left_type->int_mask_bits === null && $right_type->int_mask_bits === null)
            || !$left_type->isInt()
            || !$right_type->isInt()
        ) {
            return null;
        }

        $left_bits = self::getOperandBits($left_type);
        $right_bits = self::getOperandBits($right_type);

        if ($stmt instanceof BinaryOp\BitwiseAnd) {
            return ($left_bits ?? -1) & ($right_bits ?? -1);
        }

        if ($left_bits === null || $right_bits === null) {
            return null;
        }

        return $left_bits | $right_bits;
    }

    /**
     * Whether computing a bitwise operation pair by pair of literal operands would be too costly.
     *
     * @psalm-pure
     */
    public static function isTooCostlyPairwise(Union $left_type, Union $right_type): bool
    {
        return count($left_type->getAtomicTypes()) * count($right_type->getAtomicTypes()) > self::MAX_LITERAL_PAIRS;
    }

    /**
     * Every set of the given bits.
     *
     * @psalm-pure
     */
    public static function getType(int $bits): Union
    {
        $single_bits = [];

        for ($i = 0; $i < PHP_INT_SIZE * 8; $i++) {
            if (($bits & (1 << $i)) !== 0) {
                $single_bits[] = 1 << $i;
            }
        }

        if ($single_bits === []) {
            return Type::getInt(false, 0);
        }

        if (count($single_bits) > self::MAX_LITERAL_BITS) {
            return Type::getInt();
        }

        return new Union(TypeParser::getComputedIntsFromMask($single_bits));
    }

    /**
     * The bits an operand of a bitwise operation may have, or null when they are unknown.
     *
     * @psalm-pure
     */
    private static function getOperandBits(Union $type): ?int
    {
        if ($type->int_mask_bits !== null) {
            return $type->int_mask_bits;
        }

        return self::getExpandedBits(array_values($type->getAtomicTypes()));
    }
}
