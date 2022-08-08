<?php

namespace Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class TLowercaseString extends TString
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'lowercase-string';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
