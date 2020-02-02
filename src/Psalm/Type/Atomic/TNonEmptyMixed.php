<?php
namespace Psalm\Type\Atomic;

class TNonEmptyMixed extends TMixed
{
    /**
     * @return string
     */
    public function getId(bool $nested = false)
    {
        return 'non-empty-mixed';
    }
}
