<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

/**
 * Represents a non-empty list
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
    public function __construct(Union $type_param, ?int $count = null, ?int $min_count = null)
    {
        $this->type_param = $type_param;
        $this->count = $count;
        $this->min_count = $min_count;
    }

    public function getAssertionString(): string
    {
        return 'non-empty-list';
    }
}
