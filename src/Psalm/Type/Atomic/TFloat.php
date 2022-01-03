<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `float` type, where the exact value is unknown.
 */
class TFloat extends Scalar
{
    public function __toString(): string
    {
        return 'float';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'float';
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
        return $analysis_php_version_id >= 70000 ? 'float' : null;
    }
}
