<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class IsLessThan extends Assertion
{
    public int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getNegation(): Assertion
    {
        return new IsGreaterThanOrEqualTo($this->value);
    }

    public function __toString(): string
    {
        return '<' . $this->value;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsGreaterThanOrEqualTo && $this->value === $assertion->value;
    }

    public function doesFilterNull(): bool
    {
        return $this->value === 0;
    }
}
