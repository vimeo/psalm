<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class IsLessThanOrEqualTo extends Assertion
{
    public int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getNegation(): Assertion
    {
        return new IsGreaterThan($this->value);
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!>' . $this->value;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsGreaterThan && $this->value === $assertion->value;
    }

    public function doesFilterNullOrFalse(): bool
    {
        return false;
    }
}
