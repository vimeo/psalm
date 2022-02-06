<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class IsNotIsset extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsIsset();
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!isset';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotIsset;
    }
}
