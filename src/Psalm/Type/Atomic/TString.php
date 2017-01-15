<?php
namespace Psalm\Type\Atomic;

class TString extends Scalar
{
    public function __toString()
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'string';
    }
}
