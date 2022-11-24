<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class IsClassNotEqual extends Assertion
{
    public string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function isNegation(): bool
    {
        return true;
    }

    public function getNegation(): Assertion
    {
        return new IsClassEqual($this->type);
    }

    public function __toString(): string
    {
        return '!=get-class-' . $this->type;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsClassEqual && $this->type === $assertion->type;
    }
}
