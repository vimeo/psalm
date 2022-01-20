<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Atomic;

class IsNotLooselyEqual extends Assertion
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

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsLooselyEqual($this->type);
    }

    /** @psalm-mutation-free */
    public function hasEquality(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!~' . $this->type->getAssertionString();
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
        return $assertion instanceof IsLooselyEqual && $this->type->getId() === $assertion->type->getId();
    }
}
