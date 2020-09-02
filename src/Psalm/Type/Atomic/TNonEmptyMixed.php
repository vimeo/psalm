<?php
namespace Psalm\Type\Atomic;

class TNonEmptyMixed extends TMixed
{
    /**
     * @return string
     */
    public function getId(bool $nested = false): string
    {
        return 'non-empty-mixed';
    }
}
