<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class Any extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function getNegation(): Assertion
    {
        return $this;
    }

    public function __toString(): string
    {
        return 'mixed';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
