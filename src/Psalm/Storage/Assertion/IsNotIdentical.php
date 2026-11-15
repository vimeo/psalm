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
final class IsNotIdentical extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param ?string $comparand what the value is compared with, when its type has more than one value
     */
    public function __construct(public readonly Atomic $type, public readonly ?string $comparand = null)
    {
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegation(): bool
    {
        return true;
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new IsIdentical($this->type, $this->comparand);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function hasEquality(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!=' . $this->type->getAssertionString()
            . ($this->comparand === null ? '' : '@' . $this->comparand);
    }

    #[Override]
    public function getAtomicType(): ?Atomic
    {
        return $this->type;
    }

    /**
     * @return static
     */
    #[Override]
    public function setAtomicType(Atomic $type): self
    {
        return new static($type, $this->comparand);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsIdentical
            && $this->type->getId() === $assertion->type->getId()
            && $this->type->getAssertionString() === $assertion->type->getAssertionString()
            && $this->comparand === $assertion->comparand;
    }
}
