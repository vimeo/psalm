<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class NonEmpty extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function getNegation(): Assertion
    {
        return new Empty_();
    }

    public function __toString(): string
    {
        return 'non-empty';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Empty_;
    }
}
