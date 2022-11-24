<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class Truthy extends Assertion
{
    protected function makeNegation(): Assertion
    {
        return new Falsy();
    }

    public function __toString(): string
    {
        return '!falsy';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Falsy;
    }
}
