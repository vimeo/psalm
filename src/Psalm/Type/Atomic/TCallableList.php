<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a list that is _also_ `callable`.
 * @psalm-immutable
 */
final class TCallableList extends TNonEmptyList
{
    public const KEY = 'callable-list';
}
