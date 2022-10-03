<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class Truthy extends Assertion
{
    public function getNegation(): Assertion
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
