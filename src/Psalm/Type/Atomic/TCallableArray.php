<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an array that is _also_ `callable`.
 */
final class TCallableArray extends TNonEmptyArray
{
    /**
     * @var string
     */
    public $value = 'callable-array';

    /**
     * @param array{0: Union, 1: Union} $type_params
     */
    public function replaceTypeParams(array $type_params): self
    {
        return new self($type_params, $this->count, $this->min_count, $this->value);
    }
}
