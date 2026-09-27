<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Interner;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 * @api
 */
final class HasMethod extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param int $method interned method name, as written */
    public function __construct(public readonly int $method)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new DoesNotHaveMethod($this->method);
    }

    public function __toString(): string
    {
        return 'method-exists-' . Interner::str($this->method);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveMethod && $this->method === $assertion->method;
    }
}
