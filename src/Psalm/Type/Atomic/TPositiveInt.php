<?php
namespace Psalm\Type\Atomic;

class TPositiveInt extends TInt
{
    public function getId(bool $nested = false): string
    {
        return 'positive-int';
    }

    /**
     * @return false
     */
    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
