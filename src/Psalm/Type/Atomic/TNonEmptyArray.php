<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;
use Psalm\Type\Union;

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
