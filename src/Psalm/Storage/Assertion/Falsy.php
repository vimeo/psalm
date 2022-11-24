<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class Falsy extends Assertion
{
    protected function makeNegation(): Assertion
    {
        return new Truthy();
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return 'falsy';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Truthy;
    }
}
