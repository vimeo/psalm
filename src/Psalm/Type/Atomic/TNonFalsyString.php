<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a string, that is also non-falsy (every string except '' and '0')
 *
 * @psalm-immutable
 */
class TNonFalsyString extends TNonEmptyString
{
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'non-falsy-string';
    }
}
