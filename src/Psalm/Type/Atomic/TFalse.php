<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `false` value type
 *
 * @psalm-immutable
 * @api
 */
final class TFalse extends TBool
{
    /** @var false */
    public bool $value = false;

    /**
     * @psalm-pure
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'false';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getId(bool $exact = true): string
    {
        return 'false';
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
        if ($analysis_php_version_id >= 8_02_00) {
            return 'false';
        }

        return parent::toPhpString($namespace, $aliased_classes, $this_class, $analysis_php_version_id);
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
