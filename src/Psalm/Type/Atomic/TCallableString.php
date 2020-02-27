<?php
namespace Psalm\Type\Atomic;

class TCallableString extends TString
{
    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        return 'callable-string';
    }

    public function getId(bool $nested = false)
    {
        return $this->getKey();
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return 'string';
    }
}
