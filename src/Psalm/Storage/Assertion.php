<?php

namespace Psalm\Storage;

use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
abstract class Assertion
{
    use ImmutableNonCloneableTrait;

    private ?Assertion $negationOf = null;

    abstract protected function makeNegation(): Assertion;

    abstract public function isNegationOf(self $assertion): bool;

    abstract public function __toString(): string;

    public function getNegation(): Assertion
    {
        if (isset($this->negationOf)) {
            return $this->negationOf;
        }
        $negation = $this->makeNegation();
        /** @psalm-suppress InaccessibleProperty, ImpurePropertyAssignment Used for caching */
        $negation->negationOf = $this;
        /** @psalm-suppress InaccessibleProperty, ImpurePropertyAssignment Used for caching */
        $this->negationOf = $negation;
        return $negation;
    }

    public function isNegation(): bool
    {
        return false;
    }

    public function hasEquality(): bool
    {
        return false;
    }

    public function getAtomicType(): ?Atomic
    {
        return null;
    }

    /**
     * @return static
     */
    public function setAtomicType(Atomic $type): self
    {
        return $this;
    }
}
