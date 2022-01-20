<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Atomic;

class IsAClass extends Assertion
{
    /** @var Atomic\TTemplateParamClass|Atomic\TNamedObject */
    public Atomic $type;
    public bool $allow_string;

    /** @param Atomic\TTemplateParamClass|Atomic\TNamedObject $type */
    public function __construct(Atomic $type, bool $allow_string)
    {
        $this->type = $type;
        $this->allow_string = $allow_string;
    }

    /** @psalm-mutation-free */
    public function getNegation(): Assertion
    {
        return new IsNotAClass($this->type, $this->allow_string);
    }

    /** @psalm-mutation-free */
    public function getAtomicType(): ?Atomic
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return 'isa-' . ($this->allow_string ? 'string-' : '') . $this->type;
    }

    /** @psalm-mutation-free */
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotAClass
            && $this->type === $assertion->type
            && $this->allow_string === $assertion->allow_string;
    }
}
