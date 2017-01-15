<?php
namespace Psalm\Type\Atomic;

class TBool extends Scalar
{
    public function __toString()
    {
        return 'bool';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'bool';
    }
}
