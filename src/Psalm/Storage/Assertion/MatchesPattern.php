<?php

declare(strict_types=1);

namespace Psalm\Storage\Assertion;

use Override;
use Psalm\Storage\Assertion;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

use function implode;

/**
 * The value matches a regular expression, which tells the characters it can hold (`preg_match('/^[a-z]+$/', $x)`):
 * it doesn't narrow the type, but the value can't carry $removed_taints. Its negation says nothing.
 *
 * With $parent_node_ids, it only holds for the value of those data-flow nodes: what a variable held when the
 * value matched was derived from it (`parse_url($url, PHP_URL_HOST)`), not what it was assigned since.
 *
 * @psalm-immutable
 * @api
 */
final class MatchesPattern extends Assertion
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param ?list<string> $parent_node_ids
     */
    public function __construct(
        public readonly int $removed_taints,
        public readonly bool $negated = false,
        public readonly ?array $parent_node_ids = null,
    ) {
    }

    #[Override]
    public function getNegation(): Assertion
    {
        return new self($this->removed_taints, !$this->negated, $this->parent_node_ids);
    }

    public function __toString(): string
    {
        return ($this->negated ? '!' : '') . 'matches-pattern-' . $this->removed_taints
            . ($this->parent_node_ids === null ? '' : '-of-' . implode(',', $this->parent_node_ids));
    }

    #[Override]
    public function isNegationOf(Assertion $assertion): bool
    {
        return $assertion instanceof self
            && $assertion->removed_taints === $this->removed_taints
            && $assertion->parent_node_ids === $this->parent_node_ids
            && $assertion->negated !== $this->negated;
    }
}
