<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a list that is _also_ `callable`.
 */
final class TCallableList extends TNonEmptyList
{
    public const KEY = 'callable-list';
}
