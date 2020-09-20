<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

/**
 * Represents a non-empty array
 */
class TCallableArray extends TNonEmptyArray
{
    /**
     * @var string
     */
    public $value = 'callable-array';

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
    }
}
