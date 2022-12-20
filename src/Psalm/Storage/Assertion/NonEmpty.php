<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class NonEmpty extends Assertion
{
    public function getNegation(): Assertion
    {
        return new Empty_();
    }

    public function __toString(): string
    {
        return 'non-empty';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Empty_;
    }
}
