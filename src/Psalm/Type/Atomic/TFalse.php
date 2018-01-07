<?php
namespace Psalm\Type\Atomic;

class TFalse extends TBool
{
    public function __toString()
    {
        return 'false';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'false';
    }

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
