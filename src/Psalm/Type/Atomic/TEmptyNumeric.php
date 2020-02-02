<?php
namespace Psalm\Type\Atomic;

class TEmptyNumeric extends TNumeric
{
    /**
     * @return string
     */
    public function getId(bool $nested = false)
    {
        return 'empty-numeric';
    }
}
