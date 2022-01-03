<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Represents any value that returns true to empty(). This is used for assertions
 */
class TAssertionEmpty extends Atomic
{
    public function __toString(): string
    {
        return 'empty';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'empty';
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
