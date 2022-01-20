<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class IsNotCountable extends Assertion
{
    public $is_negatable;

    public function __construct(bool $is_negatable)
    {
        $this->is_negatable = $is_negatable;
    }

    public function isNegation(): bool
    {
        return true;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsCountable();
    }

    public function __toString(): string
    {
        return '!countable';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsCountable;
    }
}
