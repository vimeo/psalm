<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `true` value type
 *
 * @psalm-immutable
 */
final class TTrue extends TBool
{
    /** @var true */
    public bool $value = true;

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'true';
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
