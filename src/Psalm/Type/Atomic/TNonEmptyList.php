<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a non-empty array
 */
class TNonEmptyList extends TList
{
    /**
     * @var int|null
     */
    public $count;

    public const KEY = 'non-empty-list';

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return 'non-empty-list';
    }
}
