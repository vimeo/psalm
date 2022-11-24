<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class Empty_ extends Assertion
{
    protected function makeNegation(): Assertion
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

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof NonEmpty;
    }
}
