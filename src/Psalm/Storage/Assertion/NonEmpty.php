<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class NonEmpty extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    #[Override]
    public function getNegation(): Assertion
    {
        return new Empty_();
    }

    public function __toString(): string
    {
        return 'non-empty';
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Empty_;
    }
}
