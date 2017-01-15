<?php
namespace Psalm\Type\Atomic;

class TEmpty extends Scalar
{
    public function __toString()
    {
        return 'empty';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'empty';
    }
}
