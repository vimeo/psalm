<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class IsIsset extends Assertion
{
    public function getNegation(): Assertion
    {
        return new IsNotIsset();
    }

    public function __toString(): string
    {
        return 'isset';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotIsset;
    }
}
