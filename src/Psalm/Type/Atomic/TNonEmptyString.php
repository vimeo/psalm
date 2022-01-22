<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a string, that is also non-empty (every string except '')
 */
class TNonEmptyString extends TString
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'non-empty-string';
    }
}
