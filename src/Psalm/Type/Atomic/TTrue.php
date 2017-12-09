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
    public function getKey()
    {
        return 'true';
    }
}
