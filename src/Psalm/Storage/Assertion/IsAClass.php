<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class IsAClass extends Assertion
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

    public function getNegation(): Assertion
    {
        return new IsNotAClass($this->type, $this->allow_string);
    }

    public function getAtomicType(): Atomic
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return 'isa-' . ($this->allow_string ? 'string-' : '') . $this->type;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsNotAClass
            && $this->type === $assertion->type
            && $this->allow_string === $assertion->allow_string;
    }
}
