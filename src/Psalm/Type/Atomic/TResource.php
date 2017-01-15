<?php
namespace Psalm\Type\Atomic;

class TResource extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'resource';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'resource';
    }
}
