<?php
namespace Psalm\Type\Atomic;

class TNonEmptyLowercaseString extends TNonEmptyString
{
    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        return 'string';
    }

    public function getId(bool $nested = false)
    {
        return 'non-empty-lowercase-string';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
