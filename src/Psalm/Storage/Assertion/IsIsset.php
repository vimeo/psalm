<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class IsIsset extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsNotIsset();
    }

    public function __toString(): string
    {
        return 'isset';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotIsset;
    }
}
