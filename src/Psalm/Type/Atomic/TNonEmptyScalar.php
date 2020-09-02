<?php
namespace Psalm\Type\Atomic;

class TNonEmptyScalar extends TScalar
{
    /**
     * @return string
     */
    public function getId(bool $nested = false): string
    {
        return 'non-empty-scalar';
    }
}
