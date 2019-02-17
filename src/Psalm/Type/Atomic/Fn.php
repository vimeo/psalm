<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a closure where we know the return type and params
 */
class Fn extends TNamedObject
{
    use CallableTrait;

    /**
     * @return string
     */
    public function getKey()
    {
        return 'Closure';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
