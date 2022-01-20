<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Atomic;

class IsType extends Assertion
{
    public Atomic $type;

    public function __construct(Atomic $type)
    {
        $this->type = $type;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsNotType($this->type);
    }

    public function __toString(): string
    {
        return $this->type->getId();
    }

    /** @psalm-mutation-free */
    public function getAtomicType(): ?Atomic
    {
        return $this->type;
    }

    public function setAtomicType(Atomic $type): void
    {
        $this->type = $type;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotType && $this->type->getId() === $assertion->type->getId();
    }
}
