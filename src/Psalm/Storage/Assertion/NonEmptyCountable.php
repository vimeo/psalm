<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class NonEmptyCountable extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public readonly bool $is_negatable)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return $this->is_negatable ? new NotNonEmptyCountable() : new Any();
    }

    #[Override]
    public function hasEquality(): bool
    {
        return !$this->is_negatable;
    }

    public function __toString(): string
    {
        return ($this->is_negatable ? '' : '=') . 'non-empty-countable';
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $this->is_negatable && $assertion instanceof NotNonEmptyCountable;
    }
}
