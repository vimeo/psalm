<?php
namespace Psalm\Type\Atomic;

class TEmptyScalar extends TScalar
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'empty-scalar';
    }
}
