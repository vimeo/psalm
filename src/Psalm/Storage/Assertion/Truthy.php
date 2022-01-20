<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class Truthy extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new Falsy();
    }

    public function __toString(): string
    {
        return '!falsy';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Falsy;
    }
}
