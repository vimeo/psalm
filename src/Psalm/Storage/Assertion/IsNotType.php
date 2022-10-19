<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class IsNotType extends Assertion
{
    public Atomic $type;

    public function __construct(Atomic $type)
    {
        $this->type = $type;
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function getNegation(): Assertion
    {
        return new IsType($this->type);
    }

    public function __toString(): string
    {
        return '!' . $this->type->getId();
    }

    public function getAtomicType(): ?Atomic
    {
        return $this->type;
    }

    /**
     * @return static
     */
    public function setAtomicType(Atomic $type): self
    {
        return new static($type);
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsType && $this->type->getId() === $assertion->type->getId();
    }
}
