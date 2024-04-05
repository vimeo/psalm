<?php

namespace Psalm\Type\Atomic;

use Psalm\Type;
use Psalm\Type\Union;

use function array_fill;

/**
 * You may also use the \Psalm\Type::getNonEmptyListAtomic shortcut, which creates unsealed list-like shaped arrays
 * with one non-optional element, semantically equivalent to a TNonEmptyList.
 *
 * Represents a non-empty list
 *
 * @deprecated Will be removed in Psalm v6, please use TKeyedArrays with is_list=true instead.
 * @psalm-immutable
 */
class TNonEmptyList extends TList
{
    /**
     * @var positive-int|null
     */
    public $count;

    /**
     * @var positive-int|null
     */
    public $min_count;

    /** @var non-empty-lowercase-string */
    public const KEY = 'non-empty-list';

    /**
     * Constructs a new instance of a list
     *
     * @param positive-int|null $count
     * @param positive-int|null $min_count
     */
    public function __construct(
        Union $type_param,
        ?int $count = null,
        ?int $min_count = null,
        bool $from_docblock = false
    ) {
        $this->count = $count;
        $this->min_count = $min_count;
        /** @psalm-suppress DeprecatedClass */
        parent::__construct($type_param, $from_docblock);
    }

    public function getKeyedArray(): TKeyedArray
    {
        if (!$this->count && !$this->min_count) {
            return Type::getNonEmptyListAtomic($this->type_param);
        }
        if ($this->count) {
            return new TKeyedArray(
                array_fill(0, $this->count, $this->type_param),
                null,
                null,
                true,
                $this->from_docblock,
            );
        }
        return new TKeyedArray(
            array_fill(0, $this->min_count, $this->type_param),
            null,
            [Type::getListKey(), $this->type_param],
            true,
            $this->from_docblock,
        );
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

    public function getAssertionString(): string
    {
        return 'non-empty-list';
    }
}
