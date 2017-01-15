<?php
namespace Psalm\Type\Atomic;

class TVoid extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'void';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'void';
    }
}
