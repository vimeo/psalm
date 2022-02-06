<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class DoesNotHaveMethod extends Assertion
{
    public string $method;

    public function __construct(string $method)
    {
        $this->method = $method;
    }

    public function isNegation(): bool
    {
        return true;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new HasMethod($this->method);
    }

    public function __toString(): string
    {
        return '!method-exists-' . $this->method;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof HasMethod && $assertion->method === $this->method;
    }
}
