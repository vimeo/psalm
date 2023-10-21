<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class IsGreaterThan extends Assertion
{
    public function __construct(public readonly int $value)
    {
    }

    public function getNegation(): Assertion
    {
        return new IsLessThanOrEqualTo($this->value);
    }

    public function __toString(): string
    {
        return '>' . $this->value;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsLessThanOrEqualTo && $this->value === $assertion->value;
    }

    public function doesFilterNullOrFalse(): bool
    {
        return true;
    }
}
