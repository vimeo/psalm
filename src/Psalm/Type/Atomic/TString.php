<?php
namespace Psalm\Type\Atomic;

class TString extends Scalar
{
    public function __toString(): string
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        return 'string';
    }
}
