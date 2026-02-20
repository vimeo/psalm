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

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegation(): bool
    {
        return true;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getNegation(): Assertion
    {
        return new IsCountable();
    }

    /**
     * @psalm-pure
     */
    public function __toString(): string
    {
        return '!countable';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsCountable;
    }
}
