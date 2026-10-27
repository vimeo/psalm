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
 * The state is recorded as a node attribute by the analyzers of chain nodes, and MUST be refreshed on every analysis
 * because loops analyse the same nodes several times. A node without the attribute reads as None, so nodes that no
 * `?->` ever reached are left unmarked: writing None onto every call and fetch would dirty the AST pages that forked
 * analysis workers share with the parent, copying them in each worker.
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
     * Forgets the state that an earlier analysis of the node left behind, if any.
     */
    public static function reset(Expr $node): void
    {
        if ($node->hasAttribute(self::ATTRIBUTE)) {
            self::None->markOn($node);
        }
    }

    /**
     * The state of a link (method call, property/array fetch, static call) whose receiver is in this state,
     * which MUST NOT be None.
     *
     * @param bool $own_nullable whether the link's own result, analysed on the non-null receiver parts, is nullable
     * @psalm-capabilities read-props
     */
    public function afterLink(bool $own_nullable): self
    {
        return $own_nullable ? self::ShortCircuitAndNull : $this;
    }
}
