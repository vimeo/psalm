<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a list that is _also_ `callable`.
 */
final class TCallableList extends TNonEmptyList
{
    public const KEY = 'callable-list';

    public function replaceTypeParam(Union $type_param): TCallableList
    {
        return new self($type_param, $this->count, $this->min_count);
    }
}
