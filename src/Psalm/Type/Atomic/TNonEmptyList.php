<?php

namespace Psalm\Type\Atomic;

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

    public const KEY = 'non-empty-list';

    public function getAssertionString(): string
    {
        return 'non-empty-list';
    }
}
