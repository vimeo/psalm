<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `numeric` type (which can also result from an `is_numeric` check).
 *
 * @psalm-immutable
 */
class TNumeric extends Scalar
{
    public function getKey(bool $include_extra = true): string
    {
        return 'numeric';
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
}
