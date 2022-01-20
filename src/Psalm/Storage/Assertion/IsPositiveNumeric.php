<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

class IsPositiveNumeric extends Assertion
{
    public $is_negatable;

    public function __construct(bool $is_negatable)
    {
        $this->is_negatable = $is_negatable;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return $this->is_negatable ? new IsNotPositiveNumeric() : new Any();
    }

    /** @psalm-mutation-free */
    public function hasEquality(): bool
    {
        return !$this->is_negatable;
    }

    public function __toString(): string
    {
        return (!$this->is_negatable ? '=' : '') . 'positive-numeric';
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotPositiveNumeric;
    }
}
