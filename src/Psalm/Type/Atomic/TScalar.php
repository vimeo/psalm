<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `scalar` super type (which can also result from an `is_scalar` check).
 * This type encompasses `float`, `int`, `bool` and `string`.
 *
 * @psalm-immutable
 */
class TScalar extends Scalar
{
    public function getKey(bool $include_extra = true): string
    {
        return 'scalar';
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

    public function getAssertionString(): string
    {
        return 'scalar';
    }
}
