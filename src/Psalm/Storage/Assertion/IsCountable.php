<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class IsCountable extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function getNegation(): Assertion
    {
        return new IsNotCountable(true);
    }

    public function __toString(): string
    {
        return 'countable';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotCountable && $assertion->is_negatable;
    }
}
