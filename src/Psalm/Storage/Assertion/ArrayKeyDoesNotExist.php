<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class ArrayKeyDoesNotExist extends Assertion
{
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

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof ArrayKeyExists;
    }
}
