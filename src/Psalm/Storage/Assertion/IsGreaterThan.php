<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class IsGreaterThan extends Assertion
{
    public ?int $value;

    public function __construct(?int $value)
    {
        $this->value = $value;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsLessThanOrEqualTo($this->value);
    }

    public function __toString(): string
    {
        return '>' . $this->value;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsLessThanOrEqualTo && $this->value === $assertion->value;
    }
}
