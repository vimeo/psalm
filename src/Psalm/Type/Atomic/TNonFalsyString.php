<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a string, that is also non-falsy (every string except '' and '0')
 *
 * @psalm-immutable
 */
class TNonFalsyString extends TNonEmptyString
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'non-falsy-string';
    }
}
