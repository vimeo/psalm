<?php
namespace Psalm\Type\Atomic;

class TScalar extends Scalar
{
    public function __toString()
    {
        return 'scalar';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'scalar';
    }
}
