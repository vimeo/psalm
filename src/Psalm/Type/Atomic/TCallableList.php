<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

/**
 * Represents a non-empty array
 */
class TCallableList extends TNonEmptyList
{
    public const KEY = 'callable-list';
}
