<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a non-empty array
 */
class TCallableArray extends TNonEmptyArray
{
    /**
     * @var string
     */
    public $value = 'callable-array';

    public function getKey(bool $show_extra = true)
    {
        return 'array';
    }
}
