<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class NotNonEmptyCountable extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new NonEmptyCountable(true);
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!non-empty-countable';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof NonEmptyCountable && $assertion->is_negatable;
    }
}
