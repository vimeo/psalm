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
    /**
     * @psalm-pure
     */
    #[Override]
    public function getNegation(): Assertion
    {
        return new Empty_();
    }

    /**
     * @psalm-pure
     */
    public function __toString(): string
    {
        return 'non-empty';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof Empty_;
    }
}
