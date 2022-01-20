<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class HasMethod extends Assertion
{
    public string $method;

    public function __construct(string $method)
    {
        $this->method = $method;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new DoesNotHaveMethod($this->method);
    }

    public function __toString(): string
    {
        return 'method-exists-' . $this->method;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveMethod && $this->method === $assertion->method;
    }
}
