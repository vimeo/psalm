<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser\Node\Expr;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Union;

use function array_values;

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

    /**
     * Every null in the node's type is a `?->` short-circuit, but a link's own result is `mixed` or a template that
     * may hold null: later links don't report the null, and a comparison with null doesn't narrow the receiver.
     */
    case ShortCircuitAndPossiblyNull;

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
     * @param ?Union $own_type the link's own result, analysed on the non-null receiver parts
     * @param bool $own_nullable whether that result is nullable for another reason (a possibly undefined offset)
     * @psalm-capabilities read-props
     */
    public function afterLink(?Union $own_type, bool $own_nullable = false): self
    {
        if ($own_nullable || ($own_type && self::holdsNull($own_type))) {
            return self::ShortCircuitAndNull;
        }

        return $this === self::ShortCircuit && $own_type && self::canBeNull($own_type)
            ? self::ShortCircuitAndPossiblyNull
            : $this;
    }

    /**
     * Whether every null in the node's type is a `?->` short-circuit, which the later links of the chain don't report
     *
     * @psalm-capabilities read-props
     */
    public function isNullOnlyByShortCircuit(): bool
    {
        return $this === self::ShortCircuit || $this === self::ShortCircuitAndPossiblyNull;
    }

    /**
     * Whether the result of a link can be null itself: `mixed` and a template whose bound can be null can,
     * though `isNullable()` is false for them
     *
     * @psalm-pure
     */
    public static function canBeNull(Union $type): bool
    {
        if (self::holdsNull($type)) {
            return true;
        }

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (($atomic_type instanceof TMixed && !$atomic_type instanceof TNonEmptyMixed)
                || ($atomic_type instanceof TTemplateParam && self::canBeNull($atomic_type->as))
                || ($atomic_type instanceof TTemplateValueOf
                    && ($value_type = self::getArrayValueType($atomic_type->as))
                    && self::canBeNull($value_type))
                || $atomic_type instanceof TConditional
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the type holds null: `void` evaluates to null, and `value-of<T>` holds the values of the arrays in T's
     * bound
     *
     * @psalm-pure
     */
    private static function holdsNull(Union $type): bool
    {
        if ($type->isNullable() || $type->hasType('void')) {
            return true;
        }

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (($atomic_type instanceof TTemplateParam && self::holdsNull($atomic_type->as))
                || ($atomic_type instanceof TTemplateValueOf
                    && ($value_type = self::getArrayValueType($atomic_type->as))
                    && self::holdsNull($value_type))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * The values of the arrays in the bound of `value-of<T>`. Its other values are an enum's, which are never null,
     * so unlike TValueOf::getValueType() this needs no codebase.
     *
     * @psalm-pure
     */
    private static function getArrayValueType(Union $as): ?Union
    {
        $value_types = [];

        foreach ($as->getAtomicTypes() as $atomic_type) {
            $value_type = match (true) {
                $atomic_type instanceof TArray => $atomic_type->type_params[1],
                $atomic_type instanceof TKeyedArray => $atomic_type->getGenericValueType(),
                $atomic_type instanceof TTemplateParam => self::getArrayValueType($atomic_type->as),
                $atomic_type instanceof TMixed => new Union([$atomic_type]),
                default => null,
            };

            if ($value_type !== null) {
                $value_types = [...$value_types, ...array_values($value_type->getAtomicTypes())];
            }
        }

        return $value_types ? new Union($value_types) : null;
    }
}
