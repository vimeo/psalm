<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use function is_float;
use function is_nan;

/**
 * Converts literal scalar values the way PHP does, without triggering PHP's diagnostics.
 *
 * PHP 8.5 warns when NAN is coerced to string, and Psalm's error handler turns warnings into fatal errors,
 * so literals must never be converted natively when they might be NAN.
 *
 * @internal
 */
final class LiteralCast
{
    /**
     * The string PHP produces for $value; what it raises a warning for is known without raising it
     *
     * @psalm-pure
     */
    public static function toString(int|float|string $value): string
    {
        return is_float($value) && is_nan($value) ? 'NAN' : (string) $value;
    }
}
