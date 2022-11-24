<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class IsNotIsset extends Assertion
{
    protected function makeNegation(): Assertion
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

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotIsset;
    }
}
