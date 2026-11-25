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
final class IsIdentical extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param ?string $comparand what the value is compared with, when its type has more than one value
     * @param bool $of_operands the negation of an assertion on an operand of `$a !== $b`, see {@see IsNotIdentical}
     */
    public function __construct(
        public readonly Atomic $type,
        public readonly ?string $comparand = null,
        public readonly bool $of_operands = false,
    ) {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new IsNotIdentical($this->type, $this->comparand, $this->of_operands);
    }

    public function __toString(): string
    {
        return '=' . IsType::getTypeKey($this->type)
            . ($this->comparand === null ? '' : '@' . $this->comparand);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function hasEquality(): bool
    {
        return true;
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
        return new static($type, $this->comparand, $this->of_operands);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotIdentical
            && IsType::getTypeKey($this->type) === IsType::getTypeKey($assertion->type)
            && $this->comparand === $assertion->comparand;
    }
}
