<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class IsGreaterThan extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param bool $is_negatable false when the assertion is only implied by the condition rather than
     *                           equivalent to it (e.g. derived from the bounds of another int range operand),
     *                           so its negation must not be applied when the condition is false
     */
    public function __construct(public readonly int $value, public readonly bool $is_negatable = true)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return $this->is_negatable ? new IsLessThanOrEqualTo($this->value) : new Any();
    }

    #[Override]
    public function hasEquality(): bool
    {
        return !$this->is_negatable;
    }

    public function __toString(): string
    {
        return ($this->is_negatable ? '' : '=') . '>' . $this->value;
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsLessThanOrEqualTo
            && $this->is_negatable
            && $assertion->is_negatable
            && $this->value === $assertion->value;
    }

    public function doesFilterNullOrFalse(): bool
    {
        // null and false compare as bools, so a bound taken from another operand cannot filter them
        // (e.g. `null < $b` is true for $b = -1 even though `null < 0` is false)
        return $this->is_negatable;
    }
}
