<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
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

    #[Override]
    public function isNegation(): bool
    {
        return true;
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new IsCountable();
    }

    public function __toString(): string
    {
        return '!countable';
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsCountable;
    }
}
