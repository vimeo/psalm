<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser\Node\Expr;

/**
 * Where the null in the type of a `?->` chain node comes from.
 *
 * `$a?->b->c` short-circuits as a whole, so a null produced by the `?->` hop has to survive the later links
 * without being reported as "possibly null" there, whereas a null that a later link returns itself
 * (`$a?->b->maybe()->c`) is a real bug that PHP fatals on.
 *
 * The state is recorded as a node attribute by the analyzers of chain nodes, and MUST be written on every analysis
 * because loops analyse the same nodes several times.
 *
 * @internal
 */
enum NullsafeChainState
{
    /** No `?->` short-circuit can reach this node. */
    case None;

    /** Every null in the node's type is a `?->` short-circuit. */
    case ShortCircuit;

    /** A `?->` short-circuit may have happened, and the node's type has other nulls too. */
    case ShortCircuitAndNull;

    /**
     * `?->` on a receiver that is never null: nothing short-circuits, the null in the node's type is spurious
     * and is neither reported nor carried on to later links.
     */
    case NeverShortCircuits;

    private const ATTRIBUTE = 'psalm-nullsafe-chain';

    public static function of(Expr $node): self
    {
        /** @var ?self $state */
        $state = $node->getAttribute(self::ATTRIBUTE);

        return $state ?? self::None;
    }

    public function markOn(Expr $node): void
    {
        $node->setAttribute(self::ATTRIBUTE, $this);
    }

    /**
     * Whether the null in a receiver in this state is not worth a PossiblyNull* report on the next link.
     */
    public function hidesNullReports(): bool
    {
        return $this === self::ShortCircuit || $this === self::NeverShortCircuits;
    }

    /**
     * Whether the next link has to add the short-circuit null to its own result.
     */
    public function carriesNull(): bool
    {
        return $this === self::ShortCircuit || $this === self::ShortCircuitAndNull;
    }

    /**
     * The state of a link (method call, property/array fetch, static call) whose receiver is in this state.
     *
     * @param bool $own_nullable whether the link's own result, analysed on the non-null receiver parts, is nullable
     */
    public function afterLink(bool $own_nullable): self
    {
        return match ($this) {
            self::None => self::None,
            self::NeverShortCircuits => $own_nullable ? self::None : $this,
            self::ShortCircuit, self::ShortCircuitAndNull => $own_nullable ? self::ShortCircuitAndNull : $this,
        };
    }
}
