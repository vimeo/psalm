<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an array that is _also_ `callable`.
 *
 * @psalm-immutable
 */
final class TCallableArray extends TNonEmptyArray
{
    /**
     * @var string
     */
    public $value = 'callable-array';
}
