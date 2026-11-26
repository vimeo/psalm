<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function array_key_first;
use function array_values;
use function count;
use function spl_object_id;

/**
 * Replaces type variables, however deeply nested, with the type they were
 * inferred to be at their construction site: the bounds the constructor
 * arguments implied, or the declared constraint when nothing bound them.
 *
 * Used where a comparison needs a concrete structural shape and recording a
 * bound for later reconciliation would not be meaningful — bounds recorded by
 * later (possibly conflicting) uses already reconcile on their own.
 *
 * @internal
 */
final class TypeVariableResolver extends MutableTypeVisitor
{
    public bool $resolved_a_variable = false;

    /**
     * The variables resolved by the unions the traversal is inside of, by getUnrollingKey(): a bound that holds
     * the variable (`$box->add($box)` gives `_0 the bound `Box<`_0>`) keeps it there, or the traversal never ends
     *
     * @var array<string, true>
     */
    private array $unrolling = [];

    /**
     * A variable minted for an empty construction (e.g. `new Collection()`)
     * stands for `never`; the two flags select how such variables are treated
     * relative to every other variable:
     *
     * @param bool $only_never_bound resolve only those, leaving every other
     *      variable live
     * @param bool $keep_never_bound leave those live, resolving every other
     *      variable
     * @param bool $construction_site_only resolve through the construction-site
     *      bounds only; a read of the values the variable stands for (e.g. the
     *      elements a callback receives) must count every value that flowed in
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly ?Codebase $codebase,
        private readonly bool $only_never_bound = false,
        private readonly bool $keep_never_bound = false,
        private readonly bool $construction_site_only = true,
    ) {
    }

    /**
     * Resolves only the top-level type variables of a union — the shape a value
     * that is itself a bare type variable stands for, including every value
     * that flowed into it since its construction — while leaving variables
     * nested inside other atomics (e.g. `Box<`_0>`) untouched, so those can
     * still accumulate bounds. Returns the union unchanged when it has no
     * top-level variable to resolve.
     *
     * Used where a value is read out of a construction (an item of its
     * `list<`_0>`, the return of a call), since most uses of a value only know
     * concrete types. The value keeps no link to the variable: what its uses
     * require doesn't constrain a later widening of the construction, and a
     * foreach item doesn't see what the loop body adds (the `knownLimit` cases
     * of TypeVariableTest).
     *
     * @psalm-external-mutation-free
     */
    public static function resolveTopLevel(Union $type, ?Codebase $codebase): Union
    {
        $resolved_types = self::resolveAtomics($type->getAtomicTypes(), $codebase, false, false, false);

        if ($resolved_types === null) {
            return $type;
        }

        return $type->getBuilder()->setTypes(self::keyAtomics($resolved_types, $codebase))->freeze();
    }

    /**
     * Replaces every type variable among the given atomics with the atomics it
     * resolves to. A variable bound at its construction site by a value that
     * was itself carried as a type variable (e.g. `new Outer(new Inner($xs))`,
     * whose template is bound to Inner's variable) resolves to that inner
     * variable, so the replacement is repeated until no resolvable variable is
     * left. A variable met again while unrolling its own bounds is kept as is,
     * so a cycle in the bounds cannot loop forever.
     *
     * Returns null when nothing was resolved.
     *
     * @param non-empty-array<Atomic> $atomic_types
     * @param bool $only_never_bound resolve only variables minted for an empty construction
     * @param bool $keep_never_bound leave variables minted for an empty construction live
     * @param bool $construction_site_only resolve through the construction-site bounds only
     * @param array<string, true> $unrolling the variables being unrolled further up the stack
     * @return non-empty-list<Atomic>|null
     * @psalm-external-mutation-free
     */
    private static function resolveAtomics(
        array $atomic_types,
        ?Codebase $codebase,
        bool $only_never_bound,
        bool $keep_never_bound,
        bool $construction_site_only,
        array $unrolling = [],
    ): ?array {
        $resolved_types = [];
        $changed = false;

        foreach ($atomic_types as $atomic_type) {
            if (!$atomic_type instanceof TTypeVariable
                || isset($unrolling[self::getUnrollingKey($atomic_type)])
                || ($only_never_bound && !$atomic_type->isNeverBound())
                || ($keep_never_bound && $atomic_type->isNeverBound())
                || ($resolved = $atomic_type->getResolvedType($codebase, $construction_site_only)) === null
            ) {
                $resolved_types[] = $atomic_type;
                continue;
            }

            $changed = true;

            $nested_unrolling = $unrolling;
            $nested_unrolling[self::getUnrollingKey($atomic_type)] = true;

            $resolved_atomics = self::resolveAtomics(
                $resolved->getAtomicTypes(),
                $codebase,
                $only_never_bound,
                $keep_never_bound,
                $construction_site_only,
                $nested_unrolling,
            ) ?? array_values($resolved->getAtomicTypes());

            foreach ($resolved_atomics as $resolved_atomic) {
                $resolved_types[] = $resolved_atomic;
            }
        }

        if (!$changed) {
            return null;
        }

        return $resolved_types;
    }

    /**
     * A variable is its bounds, which every occurrence shares: two variables of the same name can come from
     * different trackers (one per namespace block of a file)
     *
     * @psalm-pure
     */
    private static function getUnrollingKey(TTypeVariable $variable): string
    {
        return $variable->bounds !== null ? "#" . spl_object_id($variable->bounds) : $variable->name;
    }

    /**
     * Atomics of the same key (`array<int, int>` resolved next to `array<int, string>`) are combined, not overwritten
     *
     * @param non-empty-list<Atomic> $atomic_types
     * @return non-empty-array<string, Atomic>
     * @psalm-external-mutation-free
     */
    private static function keyAtomics(array $atomic_types, ?Codebase $codebase): array
    {
        $keyed_types = [];

        foreach ($atomic_types as $atomic_type) {
            if (isset($keyed_types[$atomic_type->getKey()])) {
                return TypeCombiner::combine($atomic_types, $codebase)->getAtomicTypes();
            }

            $keyed_types[$atomic_type->getKey()] = $atomic_type;
        }

        return $keyed_types;
    }

    /**
     * @template T as TypeNode
     * @param T $node
     * @param-out T $node
     */
    #[Override]
    public function traverse(TypeNode &$node): bool
    {
        $result = parent::traverse($node);

        // a list whose element variable resolved to `never` is the empty
        // array, the same shape template replacement gives `list<never>`
        // (TKeyedArray::make); the analysis of emptiness relies on it
        if ($node instanceof TKeyedArray
            && count($node->properties) === 1
            && $node->properties[array_key_first($node->properties)]->isNever()
            && ($node->fallback_params === null || $node->fallback_params[1]->isNever())
        ) {
            $never = $node->properties[array_key_first($node->properties)]->setPossiblyUndefined(false);
            /** @var T $node */
            $node = new TArray([$never, $never], $node->from_docblock);
        }

        return $result;
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof Union) {
            return null;
        }

        // the members are traversed here: only what a variable resolves to is traversed with that variable kept,
        // so `T|array<int, T>` still resolves the `T` of its array. A type several variables share as their bound
        // is traversed once, or bounds shared along a chain of variables are expanded exponentially often; it is
        // the same object, as types equal by id (`non-empty-array`s of different counts) are combined below
        $atomic_types = [];
        $changed = false;
        /** @var array<int, array{Atomic, array<string, true>}> */
        $replacements = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            $resolved_atomics = $atomic_type instanceof TTypeVariable
                ? self::resolveAtomics(
                    [$atomic_type],
                    $this->codebase,
                    $this->only_never_bound,
                    $this->keep_never_bound,
                    $this->construction_site_only,
                    $this->unrolling,
                )
                : null;

            if ($resolved_atomics === null) {
                $original_atomic_type = $atomic_type;
                $this->traverse($atomic_type);
                $changed = $changed || $atomic_type !== $original_atomic_type;
                $atomic_types[] = $atomic_type;

                continue;
            }

            /** @var TTypeVariable $atomic_type */
            $this->resolved_a_variable = true;
            $changed = true;

            foreach ($resolved_atomics as $resolved_atomic) {
                $replacements[spl_object_id($resolved_atomic)][0] = $resolved_atomic;
                $replacements[spl_object_id($resolved_atomic)][1][self::getUnrollingKey($atomic_type)] = true;
            }
        }

        $outer_unrolling = $this->unrolling;

        foreach ($replacements as [$resolved_atomic, $variable_names]) {
            $this->unrolling = $outer_unrolling + $variable_names;
            $this->traverse($resolved_atomic);
            $atomic_types[] = $resolved_atomic;
        }

        $this->unrolling = $outer_unrolling;

        if ($changed && $atomic_types !== []) {
            // rebuild through the builder so the union's own properties survive:
            // a `list<T>` is a keyed array whose first entry is a *possibly
            // undefined* `T`, and constructing a bare `new Union` here would drop
            // that flag and silently turn the list into a non-empty-list
            $type = $type->getBuilder()->setTypes(self::keyAtomics($atomic_types, $this->codebase))->freeze();
        }

        return self::DONT_TRAVERSE_CHILDREN;
    }
}
