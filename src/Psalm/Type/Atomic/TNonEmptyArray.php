<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes array known to be non-empty of the form `non-empty-array<TKey, TValue>`.
 * It expects an array with two elements, both union types.
 */
class TNonEmptyArray extends TArray
{
    /**
     * @var positive-int|null
     */
    public $count;

    /**
     * @var positive-int|null
     */
    public $min_count;

    /**
     * @var string
     */
    public $value = 'non-empty-array';

    /**
     * @param array{0: Union, 1: Union} $type_params
     * @param positive-int|null $count
     * @param positive-int|null $min_count
     */
    public function __construct(array $type_params, ?int $count = null, ?int $min_count = null, string $value = 'non-empty-array')
    {
        $this->type_params = $type_params;
        $this->count = $count;
        $this->min_count = $min_count;
        $this->value = $value;
    }
}
