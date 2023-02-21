<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class HasAtLeastStrlen extends Assertion
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
        return new DoesNotHaveAtLeastStrlen($this->strlen);
    }

    public function __toString(): string
    {
        return 'has-at-least-strlen-' . $this->strlen;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveAtLeastStrlen && $this->strlen === $assertion->strlen;
    }
}
