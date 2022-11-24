<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class Any extends Assertion
{
    protected function makeNegation(): Assertion
    {
        return $this;
    }

    public function __toString(): string
    {
        return 'mixed';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
