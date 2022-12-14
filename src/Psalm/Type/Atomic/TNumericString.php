<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a string that's also a numeric value e.g. `"5"`. It can result from `is_string($s) && is_numeric($s)`.
 *
 * @psalm-immutable
 */
final class TNumericString extends TNonEmptyString
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'numeric-string';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'numeric-string';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'string';
    }
}
