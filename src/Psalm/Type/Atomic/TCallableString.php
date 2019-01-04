<?php
namespace Psalm\Type\Atomic;

class TCallableString extends TString
{
    /**
     * @return string
     */
    public function getKey()
    {
        return 'callable-string';
    }

    public function getId()
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
}
