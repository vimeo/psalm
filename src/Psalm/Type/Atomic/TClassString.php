<?php
namespace Psalm\Type\Atomic;

class TClassString extends TString
{
    public function __toString()
    {
        return 'class-string';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'class-string';
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
