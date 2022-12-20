<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an object-like array that is _also_ `callable`.
 *
 * @psalm-immutable
 */
final class TCallableKeyedArray extends TKeyedArray
{
    protected const NAME_ARRAY = 'callable-array';
    protected const NAME_LIST = 'callable-list';
}
