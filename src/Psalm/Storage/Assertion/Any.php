<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class Any extends Assertion
{
    public function getNegation(): Assertion
    {
        return $this;
    }

    public function __toString(): string
    {
        return 'mixed';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
