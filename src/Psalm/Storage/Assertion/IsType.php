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
     * @param bool $upper_bound_only the type is only an upper bound of what the
     *     value can be (the class of `instanceof $class` with a `class-string<Foo>`),
     *     so a value of exactly this type may still be a subclass of the real one
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
