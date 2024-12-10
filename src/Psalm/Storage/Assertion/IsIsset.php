<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class IsIsset extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function getNegation(): Assertion
    {
        return new IsNotIsset();
    }

    public function __toString(): string
    {
        return 'isset';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotIsset;
    }
}
