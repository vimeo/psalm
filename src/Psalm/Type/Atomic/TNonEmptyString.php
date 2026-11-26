<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes a string, that is also non-empty (every string except '')
 *
 * @psalm-immutable
 * @api
 */
class TNonEmptyString extends TString
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function getId(bool $exact = true): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'non-empty-string';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
