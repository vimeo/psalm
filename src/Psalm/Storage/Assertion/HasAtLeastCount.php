<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class HasAtLeastCount extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param positive-int $count */
    public function __construct(public readonly int $count)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new DoesNotHaveAtLeastCount($this->count);
    }

    public function __toString(): string
    {
        return 'has-at-least-' . $this->count;
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveAtLeastCount && $this->count === $assertion->count;
    }
}
