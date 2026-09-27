<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Interner;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 * @api
 */
final class IsClassNotEqual extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param int $type interned class name */
    public function __construct(public readonly int $type)
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
        return new IsClassEqual($this->type);
    }

    public function __toString(): string
    {
        return '!=get-class-' . Interner::str($this->type);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsClassEqual && $this->type === $assertion->type;
    }
}
