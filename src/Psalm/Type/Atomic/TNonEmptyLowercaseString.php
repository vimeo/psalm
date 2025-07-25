<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a non-empty-string where every character is lowercased. (which can also result from a `strtolower` call).
 *
 * @psalm-immutable
 */
final class TNonEmptyLowercaseString extends TNonEmptyString
{
    #[Override]
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
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
