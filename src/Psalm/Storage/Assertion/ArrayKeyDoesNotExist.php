<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class ArrayKeyDoesNotExist extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function getNegation(): Assertion
    {
        return new ArrayKeyExists();
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!array-key-exists';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof ArrayKeyExists;
    }
}
