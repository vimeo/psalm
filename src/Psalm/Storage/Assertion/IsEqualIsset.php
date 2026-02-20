<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class IsEqualIsset extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @psalm-pure
     */
    #[Override]
    public function getNegation(): Assertion
    {
        return new Any();
    }

    /**
     * @psalm-pure
     */
    public function __toString(): string
    {
        return '=isset';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function hasEquality(): bool
    {
        return true;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
