<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * Denotes the `no-return`/`never-return` type for functions that never return, either throwing an exception or
 * terminating (like the builtin `exit()`).
 *
 * @psalm-immutable
 */
final class TNever extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
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
        int $analysis_php_version_id,
    ): ?string {
        return null;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
