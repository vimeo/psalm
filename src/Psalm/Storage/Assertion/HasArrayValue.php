<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * The array contains an element equal to $value_type (strictly if $strict is true, loosely otherwise).
 *
 * @psalm-immutable
 * @api
 */
final class HasArrayValue extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;

    public function __construct(
        public readonly Atomic $value_type,
        public readonly bool $strict = true,
    ) {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new DoesNotHaveArrayValue($this->value_type, $this->strict);
    }

    public function __toString(): string
    {
        return ($this->strict ? 'has-array-value-' : 'has-loose-array-value-')
            . IsType::getTypeKey($this->value_type);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveArrayValue
            && $this->strict === $assertion->strict
            && IsType::getTypeKey($this->value_type) === IsType::getTypeKey($assertion->value_type);
    }
}
