<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class IsGreaterThanOrEqualTo extends Assertion
{
    public function __construct(public int $value)
    {
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function getNegation(): Assertion
    {
        return new IsLessThan($this->value);
    }

    public function __toString(): string
    {
        return '!<' . $this->value;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsLessThan && $this->value === $assertion->value;
    }

    public function doesFilterNullOrFalse(): bool
    {
        return $this->value !== 0;
    }
}
