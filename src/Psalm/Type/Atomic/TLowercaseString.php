<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * @psalm-immutable
 */
final class TLowercaseString extends TString
{
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'lowercase-string';
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
