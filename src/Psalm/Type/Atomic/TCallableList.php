<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a list that is _also_ `callable`.
 *
 * @deprecated Will be removed and replaced with TCallableKeyedArray in Psalm 5.1
 *
 * @psalm-immutable
 */
final class TCallableList extends TNonEmptyList
{
    public const KEY = 'callable-list';
}
