<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Union;

class NotInArray extends Assertion
{
    public Union $type;

    public function __construct(Union $type)
    {
        $this->type = $type;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new InArray($this->type);
    }

    public function __toString(): string
    {
        return '!in-array-' . $this->type;
    }

    public function isNegation(): bool
    {
        return true;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof InArray && $this->type->getId() === $assertion->type->getId();
    }
}
