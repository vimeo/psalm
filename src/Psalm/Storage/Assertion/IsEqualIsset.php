<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class IsEqualIsset extends Assertion
{
    public function getNegation(): Assertion
    {
        return new Any();
    }

    public function __toString(): string
    {
        return '=isset';
    }

    public function hasEquality(): bool
    {
        return true;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
