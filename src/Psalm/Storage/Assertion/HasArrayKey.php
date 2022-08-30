<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use UnexpectedValueException;

/**
 * @psalm-immutable
 */
final class HasArrayKey extends Assertion
{
    public $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function getNegation(): Assertion
    {
        throw new UnexpectedValueException('This should never be called');
    }

    public function __toString(): string
    {
        return 'has-array-key-' . $this->key;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return false;
    }
}
