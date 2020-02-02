<?php
namespace Psalm\Type\Atomic;

class TNonEmptyScalar extends TScalar
{
    /**
     * @return string
     */
    public function getId(bool $nested = false)
    {
        return 'non-empty-scalar';
    }
}
