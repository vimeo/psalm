<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class IsNotAClass extends Assertion
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

    public function isNegation(): bool
    {
        return true;
    }

    public function getNegation(): Assertion
    {
        return new IsAClass($this->type, $this->allow_string);
    }

    public function __toString(): string
    {
        return 'isa-' . ($this->allow_string ? 'string-' : '') . $this->type;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsAClass
            && $this->type === $assertion->type
            && $this->allow_string === $assertion->allow_string;
    }
}
