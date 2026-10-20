<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a string holding an integer in its canonical decimal form, e.g. `"0"`, `"123"` or `"-5"` (but not `"05"`,
 * `"+5"` or `"5.0"`): PHP casts it to an int when it is used as an array key.
 *
 * @psalm-immutable
 * @api
 */
final class TDecimalIntString extends TNumericString
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'decimal-int-string';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'decimal-int-string';
    }
}
