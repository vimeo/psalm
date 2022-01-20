<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class IsLessThan extends Assertion
{
    public ?int $value;

    public function __construct(?int $value)
    {
        $this->value = $value;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsGreaterThanOrEqualTo($this->value);
    }

    public function __toString(): string
    {
        return '<' . $this->value;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsGreaterThanOrEqualTo && $this->value === $assertion->value;
    }
}
