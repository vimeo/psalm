<?php

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;

/**
 * @psalm-immutable
 */
final class HasProperty extends Assertion
{
    public string $property_name;

    public function __construct(string $property_name)
    {
        $this->property_name = $property_name;
    }

    public function getNegation(): Assertion
    {
        return new DoesNotHaveProperty($this->property_name);
    }

    public function __toString(): string
    {
        return 'has-property-' . $this->property_name;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof DoesNotHaveProperty && $this->property_name === $assertion->property_name;
    }
}
