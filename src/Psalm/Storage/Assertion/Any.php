<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class Any extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return $this;
    }

    public function __toString(): string
    {
        return 'mixed';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
