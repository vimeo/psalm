<?php
namespace Psalm\Type\Atomic;

class TNumeric extends Scalar
{
    public function __toString()
    {
        return 'numeric';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'numeric';
    }
}
