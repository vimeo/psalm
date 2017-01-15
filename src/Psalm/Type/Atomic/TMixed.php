<?php
namespace Psalm\Type\Atomic;

class TMixed extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'mixed';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'mixed';
    }
}
