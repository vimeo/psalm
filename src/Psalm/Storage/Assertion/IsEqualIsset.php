<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class IsEqualIsset extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function getNegation(): Assertion
    {
        return new Any();
    }

    public function __toString(): string
    {
        return '=isset';
    }

    public function hasEquality(): bool
    {
        return true;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
