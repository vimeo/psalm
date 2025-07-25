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
    /** @var false */
    public bool $value = false;

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'false';
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
