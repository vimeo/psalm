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
final class DoesNotHaveMethod extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param int $method interned method name, as written */
    public function __construct(public readonly int $method)
    {
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function isNegation(): bool
    {
        return true;
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new HasMethod($this->method);
    }

    public function __toString(): string
    {
        return '!method-exists-' . Interner::str($this->method);
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof HasMethod && $assertion->method === $this->method;
    }
}
