<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class HasAtLeastCount extends Assertion
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
        return new DoesNotHaveAtLeastCount($this->count);
    }

    public function __toString(): string
    {
        return 'has-at-least-' . $this->count;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveAtLeastCount && $this->count === $assertion->count;
    }
}
