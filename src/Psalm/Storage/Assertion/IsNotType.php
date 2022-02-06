<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Atomic;

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

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsType($this->type);
    }

    public function __toString(): string
    {
        return '!' . $this->type->getId();
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
        return $assertion instanceof IsType && $this->type->getId() === $assertion->type->getId();
    }
}
