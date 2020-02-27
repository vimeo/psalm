<?php
namespace Psalm\Type\Atomic;

class TTrue extends TBool
{
    public function __toString()
    {
        return 'true';
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        return 'true';
    }

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
