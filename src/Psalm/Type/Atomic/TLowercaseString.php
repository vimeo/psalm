<?php

namespace Psalm\Type\Atomic;

class TLowercaseString extends TString
{
    public function getId(bool $nested = false): string
    {
        return 'lowercase-string';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
