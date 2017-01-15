<?php
namespace Psalm\Type\Atomic;

class TCallable extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'callable';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'callable';
    }
}
