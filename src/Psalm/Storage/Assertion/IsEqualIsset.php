<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class IsEqualIsset extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new Any();
    }

    public function __toString(): string
    {
        return '=isset';
    }

    /** @psalm-mutation-free */
    public function hasEquality(): bool
    {
        return true;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
