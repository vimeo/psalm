<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class IsAClass extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @param Atomic\TTemplateParamClass|Atomic\TNamedObject $type */
    public function __construct(public readonly Atomic $type, public readonly bool $allow_string)
    {
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
