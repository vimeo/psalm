<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class IsNotIdentical extends Assertion
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
        return new IsIdentical($this->type);
    }

    public function hasEquality(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!=' . $this->type->getAssertionString();
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
        return $assertion instanceof IsIdentical && $this->type->getId() === $assertion->type->getId();
    }
}
