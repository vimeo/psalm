<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 * @api
 */
final class IsNotType extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param bool $upper_bound_only the assertion only holds where it is true: where it is false, a value of
     *     this type may still be one (the name of a trait that `trait_exists()` has not loaded)
     * @param bool $exact the type the value is known to have is not only a bound: it is removed from a value that
     *     is exactly of it (the cases of a `switch` that were all negated)
     */
    public function __construct(
        public readonly Atomic $type,
        public readonly bool $upper_bound_only = false,
        public readonly bool $exact = false,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegation(): bool
    {
        return true;
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new IsType($this->type, $this->upper_bound_only);
    }

    public function __toString(): string
    {
        return '!' . $this->type->getId();
    }

    #[Override]
    public function getAtomicType(): ?Atomic
    {
        return $this->type;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    #[Override]
    public function setAtomicType(Atomic $type): self
    {
        return new static($type, $this->upper_bound_only, $this->exact);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsType && $this->type->getId() === $assertion->type->getId();
    }
}
