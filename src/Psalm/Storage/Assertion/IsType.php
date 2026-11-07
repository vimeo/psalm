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
final class IsType extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param bool $upper_bound_only the assertion only holds where it is true: where it is false, a value of
     *     this type may still be one (a subclass of the class of `instanceof $class` with a `class-string<Foo>`,
     *     the name of a trait that `trait_exists()` has not loaded)
     */
    public function __construct(public readonly Atomic $type, public readonly bool $upper_bound_only = false)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new IsNotType($this->type, $this->upper_bound_only);
    }

    public function __toString(): string
    {
        return $this->type->getId();
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
        return new static($type, $this->upper_bound_only);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotType && $this->type->getId() === $assertion->type->getId();
    }
}
