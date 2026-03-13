<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Override;
use Psalm\Type\Atomic;
use Stringable;

/**
 * @psalm-immutable
 */
abstract class Assertion implements Stringable
{
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /** @psalm-mutation-free */
    abstract public function getNegation(): Assertion;

    /** @psalm-mutation-free */
    abstract public function isNegationOf(self $assertion): bool;

    /** @psalm-mutation-free */
    #[Override]
    abstract public function __toString(): string;

    /**
     * @psalm-pure
     */
    public function isNegation(): bool
    {
        return false;
    }

    /**
     * @psalm-pure
     */
    public function hasEquality(): bool
    {
        return false;
    }

    /**
     * @psalm-pure
     */
    public function getAtomicType(): ?Atomic
    {
        return null;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function setAtomicType(Atomic $type): self
    {
        return $this;
    }
}
