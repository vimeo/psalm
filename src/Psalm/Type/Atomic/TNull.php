<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * Denotes the `null` type
 *
 * @psalm-immutable
 * @api
 */
final class TNull extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @psalm-pure
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'null';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getId(bool $exact = true): string
    {
        return 'null';
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return $analysis_php_version_id >= 8_02_00 ? 'null' : null;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return $analysis_php_version_id >= 8_02_00;
    }
}
