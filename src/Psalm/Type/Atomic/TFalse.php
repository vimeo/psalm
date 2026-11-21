<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `false` value type
 *
 * @psalm-immutable
 */
final class TFalse extends TBool
{
    /**
     * @readonly
     * @var false
     */
    public bool $value = false;

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'false';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
	#[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): string {
        if ($analysis_php_version_id >= 8_02_00) {
            return $this->getKey();
        }

        // from >= 7_00_00
        return 'bool';
    }

	#[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return $analysis_php_version_id >= 8_02_00;
    }
}
