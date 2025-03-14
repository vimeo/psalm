<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `mixed` type, but empty.
 * Generated for `$x` inside the `if` statement `if (!$x) {...}` when `$x` is `mixed` outside.
 *
 * @psalm-immutable
 */
final class TEmptyMixed extends TMixed
{
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'empty-mixed';
    }
}
