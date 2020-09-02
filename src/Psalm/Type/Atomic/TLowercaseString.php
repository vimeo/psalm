<?php
namespace Psalm\Type\Atomic;

class TLowercaseString extends TString
{
    /**
     * @return string
     */
    public function getKey(bool $include_extra = true): string
    {
        return 'string';
    }

    public function getId(bool $nested = false): string
    {
        return 'lowercase-string';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
