<?php
namespace Psalm\Type\Atomic;

class TNonEmptyScalar extends TScalar
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'non-empty-scalar';
    }
}
