<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a string that doesn't hold an integer in its canonical decimal form, e.g. `""`, `"abc"`, `"05"` or `"5.0"`:
 * it stays a string when it is used as an array key.
 *
 * @psalm-immutable
 * @api
 */
final class TNonDecimalIntString extends TString
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

        return 'non-decimal-int-string';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'non-decimal-int-string';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getAssertionString(): string
    {
        return 'string';
    }
}
