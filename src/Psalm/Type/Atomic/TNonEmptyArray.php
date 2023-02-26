<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

/**
 * Denotes array known to be non-empty of the form `non-empty-array<TKey, TValue>`.
 * It expects an array with two elements, both union types.
 *
 * @psalm-immutable
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
     * @param array{Union, Union} $type_params
     * @param positive-int|null $count
     * @param positive-int|null $min_count
     */
    public function __construct(
        array $type_params,
        ?int $count = null,
        ?int $min_count = null,
        string $value = 'non-empty-array',
        bool $from_docblock = false
    ) {
        $this->count = $count;
        $this->min_count = $min_count;
        $this->value = $value;
        parent::__construct($type_params, $from_docblock);
    }

    /**
     * @param positive-int|null $count
     * @return static
     */
    public function setCount(?int $count): self
    {
        if ($count === $this->count) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->count = $count;
        return $cloned;
    }
}
