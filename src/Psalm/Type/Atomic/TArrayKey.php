<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `array-key` type, used for something that could be the offset of an `array`.
 *
 * @psalm-immutable
 */
class TArrayKey extends Scalar
{
    public function getKey(bool $include_extra = true): string
    {
        return 'array-key';
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
        return null;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $use_phpdoc_format ? '(int|string)' : 'array-key';
    }
}
