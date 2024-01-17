<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class IsLooselyEqual extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public readonly Atomic $type)
    {
    }

    public function getNegation(): Assertion
    {
        return new IsNotLooselyEqual($this->type);
    }

    public function __toString(): string
    {
        return '~' . $this->type->getAssertionString();
    }

    public function getAtomicType(): ?Atomic
    {
        return $this->type;
    }

    public function hasEquality(): bool
    {
        return true;
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
        return $assertion instanceof IsNotLooselyEqual && $this->type->getId() === $assertion->type->getId();
    }
}
