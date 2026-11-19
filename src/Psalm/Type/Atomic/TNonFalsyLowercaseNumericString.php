<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a numeric string other than `"0"` where every character is lowercased
 *
 * @psalm-immutable
 * @api
 */
final class TNonFalsyLowercaseNumericString extends TNonFalsyNumericString
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

        return 'lowercase-string&non-falsy-string&numeric-string';
    }
}
