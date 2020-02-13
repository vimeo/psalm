<?php
namespace Psalm\Type\Atomic;

class TLowercaseString extends TString
{
    /**
     * @return string
     */
    public function getKey()
    {
        return 'string';
    }

    public function getId(bool $nested = false)
    {
        return 'lowercase-string';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
