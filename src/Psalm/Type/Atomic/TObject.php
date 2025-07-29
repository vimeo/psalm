<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * Denotes the `object` type
 *
 * @psalm-immutable
 */
class TObject extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'object';
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
        return $analysis_php_version_id >= 7_02_00 ? $this->getKey() : null;
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return true;
    }
}
