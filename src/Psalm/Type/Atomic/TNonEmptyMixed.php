<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `mixed` type, but not empty.
 * Generated for `$x` inside the `if` statement `if ($x) {...}` when `$x` is `mixed` outside.
 *
 * @psalm-immutable
 * @api
 */
final class TNonEmptyMixed extends TMixed
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function getId(bool $exact = true): string
    {
        return 'non-empty-mixed';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
