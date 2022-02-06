<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class Empty_ extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new NonEmpty();
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!non-empty';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof NonEmpty;
    }
}
