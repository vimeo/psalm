<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use function is_float;
use function is_nan;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Converts literal scalar values the way PHP does, without triggering PHP's diagnostics.
 *
 * PHP 8.5 warns when a non-finite or out of range float is converted to int (explicitly, or implicitly by
 * `%`, bitwise operators and array offsets), and when NAN is coerced to string. Psalm's error handler turns
 * such warnings into fatal errors, so literal folding must never perform these conversions natively.
 *
 * @internal
 */
final class LiteralCast
{
    /**
     * Returns the int PHP would produce, or null when a float is not representable as an int
     * (NAN, INF, or outside the int range), in which case the result is platform dependent and no literal
     * can be inferred.
     *
     * @psalm-pure
     */
    public static function toInt(int|float|string $value): ?int
    {
        if (!is_float($value)) {
            return (int) $value;
        }

        // (float) PHP_INT_MAX rounds up to 2^63, which is itself out of range; NAN fails both comparisons
        if ($value >= (float) PHP_INT_MIN && $value < (float) PHP_INT_MAX) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Returns the string PHP would produce.
     *
     * @psalm-pure
     */
    public static function toString(bool|int|float|string $value): string
    {
        return is_float($value) && is_nan($value) ? 'NAN' : (string) $value;
    }
}
