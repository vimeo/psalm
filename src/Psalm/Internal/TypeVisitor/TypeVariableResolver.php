<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function array_key_first;
use function array_values;
use function assert;
use function count;

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
     * A variable minted for an empty construction (e.g. `new Collection()`)
     * stands for `never`; the two flags select how such variables are treated
     * relative to every other variable:
     *
     * @param bool $only_never_bound resolve only those, leaving every other
     *      variable live
     * @param bool $keep_never_bound leave those live, resolving every other
     *      variable
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly ?Codebase $codebase,
        private readonly bool $only_never_bound = false,
        private readonly bool $keep_never_bound = false,
    ) {
    }

    /**
     * Resolves only the top-level type variables of a union — the shape a value
     * that is itself a bare type variable stands for — while leaving variables
     * nested inside other atomics (e.g. `Box<`_0>`) untouched, so those can
     * still accumulate bounds. Returns the union unchanged when it has no
     * top-level variable to resolve.
     *
     * @psalm-external-mutation-free
     */
    public static function resolveTopLevel(Union $type, ?Codebase $codebase): Union
    {
        $resolved_types = self::resolveAtomics($type->getAtomicTypes(), $codebase, false, false);

        if ($resolved_types === null) {
            return $type;
        }

        return $type->getBuilder()->setTypes(self::keyAtomics($resolved_types))->freeze();
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
     * @param array<string, true> $unrolling the variables being unrolled further up the stack
     * @return non-empty-list<Atomic>|null
     * @psalm-external-mutation-free
     */
    private static function resolveAtomics(
        array $atomic_types,
        ?Codebase $codebase,
        bool $only_never_bound,
        bool $keep_never_bound,
        array $unrolling = [],
    ): ?array {
        $resolved_types = [];
        $changed = false;

        foreach ($atomic_types as $atomic_type) {
            if (!$atomic_type instanceof TTypeVariable
                || isset($unrolling[$atomic_type->name])
                || ($only_never_bound && !$atomic_type->isNeverBound())
                || ($keep_never_bound && $atomic_type->isNeverBound())
                || ($resolved = $atomic_type->getResolvedType($codebase)) === null
            ) {
                $resolved_types[] = $atomic_type;
                continue;
            }

            $changed = true;

            $nested_unrolling = $unrolling;
            $nested_unrolling[$atomic_type->name] = true;

            $resolved_atomics = self::resolveAtomics(
                $resolved->getAtomicTypes(),
                $codebase,
                $only_never_bound,
                $keep_never_bound,
                $nested_unrolling,
            ) ?? array_values($resolved->getAtomicTypes());

            foreach ($resolved_atomics as $resolved_atomic) {
                $resolved_types[] = $resolved_atomic;
            }
        }

        if (!$changed) {
            return null;
        }

        // every atomic of the (non-empty) input contributed at least one atomic
        assert($resolved_types !== []);

        return $resolved_types;
    }

    /**
     * @param non-empty-list<Atomic> $atomic_types
     * @return non-empty-array<string, Atomic>
     * @psalm-pure
     */
    private static function keyAtomics(array $atomic_types): array
    {
        $keyed_types = [];

        foreach ($atomic_types as $atomic_type) {
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

        $resolved_types = self::resolveAtomics(
            $type->getAtomicTypes(),
            $this->codebase,
            $this->only_never_bound,
            $this->keep_never_bound,
        );

        if ($resolved_types !== null) {
            $this->resolved_a_variable = true;
            // rebuild through the builder so the union's own properties survive:
            // a `list<T>` is a keyed array whose first entry is a *possibly
            // undefined* `T`, and constructing a bare `new Union` here would drop
            // that flag and silently turn the list into a non-empty-list
            $type = $type->getBuilder()->setTypes(self::keyAtomics($resolved_types))->freeze();
        }

        return null;
    }
}
