<?php
namespace Psalm\Type\Atomic;

class TNull extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'null';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'null';
    }
}
