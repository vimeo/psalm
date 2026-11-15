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
final class IsNotType extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param bool $exact the type the value is known to have is not only a bound: it is removed from a value that
     *     is exactly of it (the cases of a `switch` that were all negated)
     */
    public function __construct(
        public readonly Atomic $type,
        public readonly bool $exact = false,
    ) {
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
        return new IsType($this->type);
    }

    public function __toString(): string
    {
        return '!' . IsType::getTypeKey($this->type);
    }

    #[Override]
    public function getAtomicType(): ?Atomic
    {
        return $this->type;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    #[Override]
    public function setAtomicType(Atomic $type): self
    {
        return new static($type, $this->exact);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsType
            && IsType::getTypeKey($this->type) === IsType::getTypeKey($assertion->type);
    }
}
