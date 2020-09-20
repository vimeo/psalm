<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

/**
 * Represents a non-empty array
 */
class TNonEmptyArray extends TArray
{
    /**
     * @var int|null
     */
    public $count;

    /**
     * @var string
     */
    public $value = 'non-empty-array';
}
