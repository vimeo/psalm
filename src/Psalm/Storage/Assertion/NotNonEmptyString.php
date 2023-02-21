<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class NotNonEmptyString extends Assertion
{
    public function getNegation(): Assertion
    {
        return new NonEmptyString(true);
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!non-empty-string';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof NonEmptyString && $assertion->is_negatable;
    }
}
