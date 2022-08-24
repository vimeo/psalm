<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an object-like array that is _also_ `callable`.
 */
final class TCallableKeyedArray extends TKeyedArray
{
    public const KEY = 'callable-array';

    /**
     * @param non-empty-array<string|int, Union> $properties
     */
    public function setProperties(array $properties): self
    {
        return new self($properties, $this->class_strings, $this->sealed, $this->previous_key_type, $this->previous_value_type, $this->is_list);
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
    }
}
