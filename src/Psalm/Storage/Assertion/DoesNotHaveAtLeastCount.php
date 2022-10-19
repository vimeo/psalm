<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class DoesNotHaveAtLeastCount extends Assertion
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
        return new HasAtLeastCount($this->count);
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '!has-at-least-' . $this->count;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof HasAtLeastCount && $this->count === $assertion->count;
    }
}
