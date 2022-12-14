<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `trait-string` type, used to describe a string representing a valid PHP trait.
 *
 * @psalm-immutable
 */
final class TTraitString extends TString
{
    public function getKey(bool $include_extra = true): string
    {
        return 'trait-string';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): ?string {
        return 'string';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return 'trait-string';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
