<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class NonEmptyCountable extends Assertion
{
    public $is_negatable;

    public function __construct(bool $is_negatable)
    {
        $this->is_negatable = $is_negatable;
    }

    public function getNegation(): Assertion
    {
        return $this->is_negatable ? new NotNonEmptyCountable() : new Any();
    }

    public function hasEquality(): bool
    {
        return !$this->is_negatable;
    }

    public function __toString(): string
    {
        return ($this->is_negatable ? '' : '=') . 'non-empty-countable';
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $this->is_negatable && $assertion instanceof NotNonEmptyCountable;
    }
}
