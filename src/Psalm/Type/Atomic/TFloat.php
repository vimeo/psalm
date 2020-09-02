<?php
namespace Psalm\Type\Atomic;

class TFloat extends Scalar
{
    public function __toString(): string
    {
        return 'float';
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true): string
    {
        return 'float';
    }
}
