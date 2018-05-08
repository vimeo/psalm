<?php
namespace Psalm\Type\Atomic;

class TEmptyMixed extends TMixed
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'empty-mixed';
    }
}
