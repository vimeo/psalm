<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use UnexpectedValueException;

/**
 * @psalm-immutable
 */
final class HasArrayKey extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public readonly string $key)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        throw new UnexpectedValueException('This should never be called');
    }

    public function __toString(): string
    {
        return 'has-array-key-' . $this->key;
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
