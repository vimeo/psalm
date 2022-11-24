<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class HasMethod extends Assertion
{
    public string $method;

    public function __construct(string $method)
    {
        $this->method = $method;
    }

    public function getNegation(): Assertion
    {
        return new DoesNotHaveMethod($this->method);
    }

    public function __toString(): string
    {
        return 'method-exists-' . $this->method;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveMethod && $this->method === $assertion->method;
    }
}
