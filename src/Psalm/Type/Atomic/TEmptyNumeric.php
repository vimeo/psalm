<?php
namespace Psalm\Type\Atomic;

class TEmptyNumeric extends TNumeric
{
    /**
     * @return string
     */
    public function getId(bool $nested = false): string
    {
        return 'empty-numeric';
    }
}
