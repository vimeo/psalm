<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

/**
 * Denotes the `true` value type
 *
 * @psalm-immutable
 */
final class TTrue extends TBool
{
    /** @var true */
    public bool $value = true;

    public function getKey(bool $include_extra = true): string
    {
        return 'true';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
