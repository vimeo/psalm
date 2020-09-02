<?php
namespace Psalm\Type\Atomic;

class TInt extends Scalar
{
    public function __toString(): string
    {
        return 'int';
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true): string
    {
        return 'int';
    }
}
