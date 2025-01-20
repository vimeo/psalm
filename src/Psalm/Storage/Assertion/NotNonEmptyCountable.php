<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class NotNonEmptyCountable extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function getNegation(): Assertion
    {
        return new NonEmptyCountable(true);
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!non-empty-countable';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof NonEmptyCountable && $assertion->is_negatable;
    }
}
