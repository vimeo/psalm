<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class ArrayKeyExists extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @psalm-pure
     */
    #[Override]
    public function getNegation(): Assertion
    {
        return new ArrayKeyDoesNotExist();
    }

    /**
     * @psalm-pure
     */
    public function __toString(): string
    {
        return 'array-key-exists';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof ArrayKeyDoesNotExist;
    }
}
