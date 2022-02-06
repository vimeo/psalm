<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

final class IsClassEqual extends Assertion
{
    public string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsClassNotEqual($this->type);
    }

    /** @psalm-mutation-free */
    public function hasEquality(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '=get-class-' . $this->type;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsClassNotEqual && $this->type === $assertion->type;
    }
}
