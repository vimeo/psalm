<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a numeric string that is not falsy, i.e. a numeric string other than `"0"`
 *
 * @psalm-immutable
 * @api
 */
class TNonFalsyNumericString extends TNumericString
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

        return 'non-falsy-string&numeric-string';
    }
}
