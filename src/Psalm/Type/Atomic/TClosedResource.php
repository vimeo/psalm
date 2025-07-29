<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * Denotes the `resource` type that has been closed (e.g. a file handle through `fclose()`).
 *
 * @psalm-immutable
 */
final class TClosedResource extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'closed-resource';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return null;
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
