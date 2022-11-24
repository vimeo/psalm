<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class ArrayKeyExists extends Assertion
{
    protected function makeNegation(): Assertion
    {
        return new ArrayKeyDoesNotExist();
    }

    public function __toString(): string
    {
        return 'array-key-exists';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof ArrayKeyDoesNotExist;
    }
}
