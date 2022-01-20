<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class NonEmpty extends Assertion
{
    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new Empty_();
    }

    public function __toString(): string
    {
        return 'non-empty';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Empty_;
    }
}
