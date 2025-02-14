<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class Empty_ extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    #[Override]
    public function getNegation(): Assertion
    {
        return new NonEmpty();
    }

    #[Override]
    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!non-empty';
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof NonEmpty;
    }
}
