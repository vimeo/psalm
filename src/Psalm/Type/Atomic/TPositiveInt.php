<?php
namespace Psalm\Type\Atomic;

class TPositiveInt extends TInt
{
    /**
     * @return string
     */
    public function getId(bool $nested = false)
    {
        return 'positive-int';
    }

    /**
     * @return false
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
