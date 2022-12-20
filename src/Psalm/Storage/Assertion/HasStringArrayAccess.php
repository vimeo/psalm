<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use UnexpectedValueException;

/**
 * @psalm-immutable
 */
final class HasStringArrayAccess extends Assertion
{
    public function getNegation(): Assertion
    {
        throw new UnexpectedValueException('This should never be called');
    }

    public function __toString(): string
    {
        return 'has-string-array-access';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
