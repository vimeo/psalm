<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TLiteralString;

use function mb_strlen;

/**
 * @psalm-immutable
 * @api
 */
final class IsType extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public readonly Atomic $type)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new IsNotType($this->type);
    }

    public function __toString(): string
    {
        return self::getTypeKey($this->type);
    }

    /**
     * The id of the type, but with the whole of a literal string that the id would cut short
     *
     * @internal
     * @psalm-pure
     */
    public static function getTypeKey(Atomic $type): string
    {
        return $type instanceof TLiteralString && mb_strlen($type->value) > 80
            ? $type->getAssertionString()
            : $type->getId();
    }

    #[Override]
    public function getAtomicType(): ?Atomic
    {
        return $this->type;
    }

    /**
     * @return static
     * @psalm-pure
     */
    #[Override]
    public function setAtomicType(Atomic $type): self
    {
        return new static($type);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotType
            && self::getTypeKey($this->type) === self::getTypeKey($assertion->type);
    }
}
