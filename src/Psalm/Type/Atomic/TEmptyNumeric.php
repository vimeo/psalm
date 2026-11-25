<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `numeric` type that's also empty (which can also result from an `is_numeric` and `empty` check).
 *
 * @psalm-immutable
 * @api
 */
final class TEmptyNumeric extends TNumeric
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function getId(bool $exact = true): string
    {
        return 'empty-numeric';
    }

    /**
     * empty-numeric is no docblock type: written as numeric
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
        return 'numeric';
    }
}
