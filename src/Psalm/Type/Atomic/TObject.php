<?php
namespace Psalm\Type\Atomic;

class TObject extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'object';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'object';
    }
}
