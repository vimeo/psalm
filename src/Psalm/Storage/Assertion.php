<?php

namespace Psalm\Storage;

use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
abstract class Assertion
{
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    abstract public function getNegation(): Assertion;

    abstract public function isNegationOf(self $assertion): bool;

    abstract public function __toString(): string;

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
