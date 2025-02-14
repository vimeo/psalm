<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class HasMethod extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public readonly string $method)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new DoesNotHaveMethod($this->method);
    }

    public function __toString(): string
    {
        return 'method-exists-' . $this->method;
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveMethod && $this->method === $assertion->method;
    }
}
