<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * The value matches a regular expression, which tells the characters it can hold (`preg_match('/^[a-z]+$/', $x)`):
 * it doesn't narrow the type, but the value can't carry $removed_taints. Its negation says nothing.
 *
 * @psalm-immutable
 * @api
 */
final class MatchesPattern extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;

    public function __construct(public readonly int $removed_taints, public readonly bool $negated = false)
    {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new self($this->removed_taints, !$this->negated);
    }

    public function __toString(): string
    {
        return ($this->negated ? '!' : '') . 'matches-pattern-' . $this->removed_taints;
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof self
            && $assertion->removed_taints === $this->removed_taints
            && $assertion->negated !== $this->negated;
    }
}
