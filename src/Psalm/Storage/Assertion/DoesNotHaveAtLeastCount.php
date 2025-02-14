<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class DoesNotHaveAtLeastCount extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param positive-int $count */
    public function __construct(public readonly int $count)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new HasAtLeastCount($this->count);
    }

    #[Override]
    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!has-at-least-' . $this->count;
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof HasAtLeastCount && $this->count === $assertion->count;
    }
}
