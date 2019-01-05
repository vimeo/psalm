<?php
namespace Psalm\Type\Atomic;

class TNumericString extends TString
{
    /**
     * @return string
     */
    public function getKey()
    {
        return 'numeric-string';
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

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return 'string';
    }
}
