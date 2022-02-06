<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class ArrayKeyExists extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new ArrayKeyDoesNotExist();
    }

    public function __toString(): string
    {
        return 'array-key-exists';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof ArrayKeyDoesNotExist;
    }
}
