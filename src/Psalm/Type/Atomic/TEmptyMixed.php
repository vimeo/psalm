<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `mixed` type, but empty.
 * Generated for `$x` inside the `if` statement `if (!$x) {...}` when `$x` is `mixed` outside.
 *
 * @psalm-immutable
 */
final class TEmptyMixed extends TMixed
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'empty-mixed';
    }
}
