<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class IsCountable extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsNotCountable(true);
    }

    public function __toString(): string
    {
        return 'countable';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotCountable && $assertion->is_negatable;
    }
}
