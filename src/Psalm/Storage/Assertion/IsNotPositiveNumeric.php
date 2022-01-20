<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class IsNotPositiveNumeric extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsPositiveNumeric(true);
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!positive-numeric';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsPositiveNumeric;
    }
}
