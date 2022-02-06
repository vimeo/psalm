<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Union;

final class InArray extends Assertion
{
    public Union $type;

    public function __construct(Union $type)
    {
        $this->type = $type;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new NotInArray($this->type);
    }

    public function __toString(): string
    {
        return 'in-array-' . $this->type->getId();
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof NotInArray && $this->type->getId() === $assertion->type->getId();
    }
}
