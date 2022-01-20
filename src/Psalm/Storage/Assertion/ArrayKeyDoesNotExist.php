<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class ArrayKeyDoesNotExist extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new ArrayKeyExists();
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!array-key-exists';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof ArrayKeyExists;
    }
}
