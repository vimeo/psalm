<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class Falsy extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
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

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Truthy;
    }
}
