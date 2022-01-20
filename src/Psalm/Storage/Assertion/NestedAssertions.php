<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

use function json_encode;

use const JSON_THROW_ON_ERROR;

class NestedAssertions extends Assertion
{
    /** @var array<string, list<list<Assertion>>> */
    public array $assertions;

    /** @param array<string, list<list<Assertion>>> $assertions */
    public function __construct(array $assertions)
    {
        $this->assertions = $assertions;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new NotNestedAssertions($this->assertions);
    }

    public function __toString(): string
    {
        return '@' . json_encode($this->assertions, JSON_THROW_ON_ERROR);
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
