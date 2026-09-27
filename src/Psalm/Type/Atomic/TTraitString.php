<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `trait-string` type, used to describe a string representing a valid PHP trait.
 *
 * @psalm-immutable
 * @api
 */
final class TTraitString extends TString
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'trait-string';
    }

    /**
     * @param array<int, int> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toPhpString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return 'string';
    }

    /**
     * @param array<int, int> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toNamespacedString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        bool $use_phpdoc_format,
    ): string {
        return 'trait-string';
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
