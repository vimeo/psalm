<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a non-falsy string where every character is lowercased, written `lowercase-string&non-falsy-string`
 *
 * @psalm-immutable
 * @api
 */
final class TNonFalsyLowercaseString extends TNonFalsyString
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'lowercase-string&non-falsy-string';
    }

    /**
     * @return false
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
