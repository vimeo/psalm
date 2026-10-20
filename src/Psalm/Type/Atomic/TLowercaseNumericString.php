<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a numeric string where every character is lowercased, e.g. `"1e3"` but not `"1E3"`
 *
 * @psalm-immutable
 * @api
 */
final class TLowercaseNumericString extends TNumericString
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

        return 'lowercase-string&numeric-string';
    }
}
