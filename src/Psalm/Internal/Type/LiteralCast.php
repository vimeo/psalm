<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use function filter_var;
use function is_float;
use function is_int;
use function is_nan;

use const FILTER_VALIDATE_INT;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Converts literal scalar values the way PHP does, without triggering PHP's diagnostics.
 *
 * PHP 8.5 warns when a non-finite or out of range float is converted to int (explicitly, or implicitly by
 * `%`, bitwise operators and array offsets), and when NAN is coerced to string. PHP 8.5.0 and 8.5.1 also warn
 * when a string that parses as such a float is converted to int. Psalm's error handler turns such warnings
 * into fatal errors, so literal folding must never perform these conversions natively.
 *
 * @psalm-immutable
 * @internal
 */
final class LiteralCast
{
    /**
     * Returns the int PHP would produce, or null when a float, or a string PHP parses as a float, is not
     * representable as an int (NAN, INF, or outside the int range), in which case the result is platform
     * dependent and no literal can be inferred.
     *
     * @psalm-pure
     */
    public static function toInt(int|float|string $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return self::isRepresentableAsInt($value) ? (int) $value : null;
        }

        // (float) never warns and parses the string's numeric prefix the same way (int) does
        if (self::isRepresentableAsInt((float) $value)) {
            return (int) $value;
        }

        // e.g. '9223372036854775807' is an int, but its float rounds to 2^63
        $int = filter_var($value, FILTER_VALIDATE_INT);

        return $int === false ? null : $int;
    }

    /**
     * @psalm-pure
     */
    private static function isRepresentableAsInt(float $value): bool
    {
        // (float) PHP_INT_MAX rounds up to 2^63, which is itself out of range; NAN fails both comparisons
        return $value >= (float) PHP_INT_MIN && $value < (float) PHP_INT_MAX;
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
