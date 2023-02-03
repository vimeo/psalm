<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class HasExactStrlen extends Assertion
{
    /** @var positive-int */
    public $strlen;

    /** @param positive-int $strlen */
    public function __construct(int $strlen)
    {
        $this->strlen = $strlen;
    }

    public function getNegation(): Assertion
    {
        return new DoesNotHaveExactStrlen($this->strlen);
    }

    public function hasEquality(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '=has-exact-strlen-' . $this->strlen;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveExactStrlen && $this->strlen === $assertion->strlen;
    }
}
