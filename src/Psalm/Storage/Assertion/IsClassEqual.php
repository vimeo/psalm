<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class IsClassEqual extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public readonly string $type)
    {
    }

    public function getNegation(): Assertion
    {
        return new IsClassNotEqual($this->type);
    }

    public function hasEquality(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return '=get-class-' . $this->type;
    }

    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof IsClassNotEqual && $this->type === $assertion->type;
    }
}
