<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `float` type, where the exact value is unknown.
 *
 * @psalm-immutable
 * @api
 */
class TFloat extends Scalar
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'float';
    }

    /**
     * @param array<int, int> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toPhpString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return $analysis_php_version_id >= 7_00_00 ? 'float' : null;
    }
}
