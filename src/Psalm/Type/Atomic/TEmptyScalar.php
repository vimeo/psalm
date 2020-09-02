<?php
namespace Psalm\Type\Atomic;

class TEmptyScalar extends TScalar
{
    /**
     * @return string
     */
    public function getId(bool $nested = false): string
    {
        return 'empty-scalar';
    }
}
