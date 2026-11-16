<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * An assertion that doesn't narrow the type, only puts the key into the clause (e.g. `defined('X')`).
 * Its negation is a distinct literal that doesn't narrow either.
 *
 * @psalm-immutable
 * @api
 */
final class Any extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;

    public function __construct(public readonly bool $negated = false)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new self(!$this->negated);
    }

    public function __toString(): string
    {
        return $this->negated ? '!mixed' : 'mixed';
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof self && $assertion->negated !== $this->negated;
    }
}
