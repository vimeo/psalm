<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `mixed` type, but empty.
 * Generated for `$x` inside the `if` statement `if (!$x) {...}` when `$x` is `mixed` outside.
 *
 * @psalm-immutable
 * @api
 */
final class TEmptyMixed extends TMixed
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function getId(bool $exact = true): string
    {
        return 'empty-mixed';
    }

    /**
     * empty-mixed is no docblock type: written as mixed
     *
     * @param array<lowercase-string, string> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        return 'mixed';
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
