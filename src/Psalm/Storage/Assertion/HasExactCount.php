<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class HasExactCount extends Assertion
{
    /** @var positive-int */
    public $count;

    /** @param positive-int $count */
    public function __construct(int $count)
    {
        $this->count = $count;
    }

    public function getNegation(): Assertion
    {
        return new DoesNotHaveExactCount($this->count);
    }

    public function hasEquality(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '=has-exact-count-' . $this->count;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveExactCount && $this->count === $assertion->count;
    }
}
