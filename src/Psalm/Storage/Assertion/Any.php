<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class Any extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    #[Override]
    public function getNegation(): Assertion
    {
        return $this;
    }

    public function __toString(): string
    {
        return 'mixed';
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
