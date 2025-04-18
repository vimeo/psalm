<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-immutable
 */
final class NotNestedAssertions extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param array<string, list<list<Assertion>>> $assertions */
    public function __construct(public readonly array $assertions)
    {
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function getNegation(): Assertion
    {
        return new NestedAssertions($this->assertions);
    }

    public function __toString(): string
    {
        return '!@' . json_encode($this->assertions, JSON_THROW_ON_ERROR);
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
