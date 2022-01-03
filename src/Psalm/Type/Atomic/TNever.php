<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `no-return`/`never-return` type for functions that never return, either throwing an exception or
 * terminating (like the builtin `exit()`).
 */
class TNever extends Atomic
{
    public function __toString(): string
    {
        return 'never';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'never';
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
