<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class IsLessThanOrEqualTo extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public readonly int $value)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new IsGreaterThan($this->value);
    }

    #[Override]
    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!>' . $this->value;
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsGreaterThan && $this->value === $assertion->value;
    }

    public function doesFilterNullOrFalse(): bool
    {
        return false;
    }
}
