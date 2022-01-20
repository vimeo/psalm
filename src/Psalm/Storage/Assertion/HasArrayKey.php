<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use UnexpectedValueException;

class HasArrayKey extends Assertion
{
    public $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        throw new UnexpectedValueException('This should never be called');
    }

    public function __toString(): string
    {
        return 'has-array-key-' . $this->key;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
