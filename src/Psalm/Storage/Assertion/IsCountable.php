<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class IsCountable extends Assertion
{
    protected function makeNegation(): Assertion
    {
        return new IsNotCountable(true);
    }

    public function __toString(): string
    {
        return 'countable';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotCountable && $assertion->is_negatable;
    }
}
