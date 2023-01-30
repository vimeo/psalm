<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a non-empty-string where every character is lowercased. (which can also result from a `strtolower` call).
 *
 * @psalm-immutable
 */
final class TNonEmptyLowercaseString extends TNonEmptyString
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'non-empty-lowercase-string';
    }

    /**
     * @return false
     */
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
