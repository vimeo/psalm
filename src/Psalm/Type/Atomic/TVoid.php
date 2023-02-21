<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `void` type, normally just used to annotate a function/method that returns nothing
 *
 * @psalm-immutable
 */
final class TVoid extends Atomic
{
    public function getKey(bool $include_extra = true): string
    {
        return 'void';
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
        return $analysis_php_version_id >= 7_01_00 ? $this->getKey() : null;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return true;
    }
}
