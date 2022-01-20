<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class IsGreaterThanOrEqualTo extends Assertion
{
    public ?int $value;

    public function __construct(?int $value)
    {
        $this->value = $value;
    }

    public function isNegation(): bool
    {
        return true;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsLessThan($this->value);
    }

    public function __toString(): string
    {
        return '!<' . $this->value;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsLessThan && $this->value === $assertion->value;
    }
}
