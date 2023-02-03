<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class DoesNotHaveExactStrlen extends Assertion
{
    /** @var positive-int */
    public $strlen;

    /** @param positive-int $strlen */
    public function __construct(int $strlen)
    {
        $this->strlen = $strlen;
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function getNegation(): Assertion
    {
        return new HasExactStrlen($this->strlen);
    }

    public function __toString(): string
    {
        return '!has-exact-strlen-' . $this->strlen;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof HasExactStrlen && $assertion->strlen === $this->strlen;
    }
}
