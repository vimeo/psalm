<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a string, that is also non-empty (every string except '')
 *
 * @psalm-immutable
 */
class TNonEmptyString extends TString
{
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'non-empty-string';
    }
}
