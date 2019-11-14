<?php
namespace Psalm\Type\Atomic;

class TEmptyNumeric extends TNumeric
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'empty-numeric';
    }
}
