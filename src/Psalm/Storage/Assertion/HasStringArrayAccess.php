<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use UnexpectedValueException;

final class HasStringArrayAccess extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        throw new UnexpectedValueException('This should never be called');
    }

    public function __toString(): string
    {
        return 'has-string-array-access';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
