<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class IsNotCountable extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public readonly bool $is_negatable)
    {
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function getNegation(): Assertion
    {
        return new IsCountable();
    }

    public function __toString(): string
    {
        return '!countable';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsCountable;
    }
}
