<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `false` value type
 */
class TFalse extends TBool
{
    public function __toString(): string
    {
        return 'false';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'false';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
