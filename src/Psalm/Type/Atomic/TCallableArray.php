<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes an array that is _also_ `callable`.
 */
class TCallableArray extends TNonEmptyArray
{
    /** @var array<class-string<Atomic>, true> */
    public const CONTAINED_BY = parent::CONTAINED_BY + [
        self::class => true,
    ];

    /**
     * @var string
     */
    public $value = 'callable-array';
}
