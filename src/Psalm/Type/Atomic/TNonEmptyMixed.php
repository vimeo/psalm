<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `mixed` type, but not empty.
 * Generated for `$x` inside the `if` statement `if ($x) {...}` when `$x` is `mixed` outside.
 *
 * @psalm-immutable
 */
final class TNonEmptyMixed extends TMixed
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'non-empty-mixed';
    }
}
