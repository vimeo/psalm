<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class DoesNotHaveMethod extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public readonly string $method)
    {
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function getNegation(): Assertion
    {
        return new HasMethod($this->method);
    }

    public function __toString(): string
    {
        return '!method-exists-' . $this->method;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof HasMethod && $assertion->method === $this->method;
    }
}
