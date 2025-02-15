<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class HasExactCount extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param positive-int $count */
    public function __construct(public readonly int $count)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new DoesNotHaveExactCount($this->count);
    }

    #[Override]
    public function hasEquality(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '=has-exact-count-' . $this->count;
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveExactCount && $this->count === $assertion->count;
    }
}
