<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class HasExactCount extends Assertion
{
    /** @var positive-int */
    public $count;

    /** @param positive-int $count */
    public function __construct(int $count)
    {
        $this->count = $count;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new DoesNotHaveExactCount($this->count);
    }

    /** @psalm-mutation-free */
    public function hasEquality(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '=has-exact-count-' . $this->count;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveExactCount && $this->count === $assertion->count;
    }
}
