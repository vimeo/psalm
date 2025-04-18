<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class Truthy extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function getNegation(): Assertion
    {
        return new Falsy();
    }

    public function __toString(): string
    {
        return '!falsy';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Falsy;
    }
}
