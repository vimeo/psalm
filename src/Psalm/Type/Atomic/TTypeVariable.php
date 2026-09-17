<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeVariableBounds;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_slice;

/**
 * Denotes a type variable: a placeholder (e.g. `` `_0 ``) minted for a class template
 * at a construction site, where PHP has no type arguments that could pin it. Constraints
 * accumulate against it in the surrounding function-like's TypeVariableTracker while the
 * variable flows through the body, and are reconciled with each other when the
 * function-like has been analyzed.
 *
 * @psalm-immutable
 * @api
 */
final class TTypeVariable extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    public function __construct(
        public readonly string $name,
        public readonly ?TypeVariableBounds $bounds = null,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return $this->name;
    }

    /**
     * Renders the variable through the bounds recorded against it so far: the
     * union of its lower bounds, or the constraint it was minted with when
     * nothing has bound it from below.
     *
     * @psalm-suppress ImpureMethodCall the bounds accumulate while the variable
     *      flows through the function body, and the variable always displays
     *      their current state
     */
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        // Bounds are rendered through the types they hold, which may (directly
        // or through another variable) mention this variable again. A variable
        // that is already being rendered further up the stack falls back to its
        // name, so a cycle in the bounds cannot recurse forever.
        /**
         * @var array<string, true> $rendering
         * @psalm-suppress ImpureStaticVariable only tracks the variables being
         *      rendered on the current call stack
         */
        static $rendering = [];

        if (!$this->bounds
            || isset($rendering[$this->name])
            || (!$this->bounds->lower_bounds && !$this->bounds->upper_bounds)
        ) {
            return $this->name;
        }

        $rendering[$this->name] = true;

        try {
            if ($this->bounds->lower_bounds) {
                return self::widenMixedToConstraint(
                    TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                        $this->bounds->lower_bounds,
                        null,
                    ),
                    $this->bounds,
                )->getId($exact);
            }

            return $this->bounds->upper_bounds[0]->type->getId($exact);
        } finally {
            unset($rendering[$this->name]);
        }
    }

    /**
     * Resolves this variable to the shape inferred at its construction site:
     * the most specific of the bounds the constructor arguments implied, or the
     * first upper bound when nothing bound it from below. Bounds recorded by
     * later uses are ignored here — they reconcile on their own — so a read of
     * the variable sees the same concrete shape its display renders through.
     *
     * Returns null when the variable was minted with no bounds at all and there
     * is therefore nothing to resolve it to.
     *
     * @psalm-suppress ImpureMethodCall the bounds accumulate while the variable
     *      flows through the function body; resolving always reflects the
     *      construction-site inference recorded up front
     */
    public function getResolvedType(?Codebase $codebase): ?Union
    {
        if (!$this->bounds) {
            return null;
        }

        $initial_lower_bounds = array_slice(
            $this->bounds->lower_bounds,
            0,
            $this->bounds->initial_lower_bound_count,
        );

        if ($initial_lower_bounds) {
            $resolved = self::widenMixedToConstraint(
                TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                    $initial_lower_bounds,
                    $codebase,
                ),
                $this->bounds,
            );
        } elseif ($this->bounds->upper_bounds) {
            $resolved = $this->bounds->upper_bounds[0]->type;
        } else {
            return null;
        }

        return $resolved;
    }

    /**
     * A construction site whose arguments only bound the variable to `mixed`
     * (e.g. `new Collection($untyped)`) tells nothing about the shape the
     * variable stands for, so the declared constraint is the best knowledge
     * available: a read of the variable resolves to it, exactly as a template
     * inferred as `mixed` has always been replaced by its `as` type
     * (TemplateInferredTypeReplacer). A constraint that is itself `mixed`
     * adds nothing and the bound is kept.
     *
     * @psalm-mutation-free
     */
    public static function widenMixedToConstraint(Union $resolved, TypeVariableBounds $bounds): Union
    {
        if ($resolved->isMixed()
            && $bounds->upper_bounds
            && !$bounds->upper_bounds[0]->type->isMixed()
        ) {
            return $bounds->upper_bounds[0]->type;
        }

        return $resolved;
    }

    /**
     * Whether the construction site inferred `never` for this variable: it was
     * minted for an empty construction (e.g. `new Collection([])`), so until
     * something binds it from below it stands for no value at all.
     */
    public function isNeverBound(): bool
    {
        if (!$this->bounds || !$this->bounds->initial_lower_bound_count) {
            return false;
        }

        // A bound may hold another variable (a template bound by an argument
        // whose type carried one); such a bound is `never` when that variable
        // is. Variables on the current call stack are not re-entered, so a
        // cycle in the bounds cannot recurse forever.
        /**
         * @var array<string, true> $checking
         * @psalm-suppress ImpureStaticVariable only tracks the variables being
         *      checked on the current call stack
         */
        static $checking = [];

        if (isset($checking[$this->name])) {
            return false;
        }

        $checking[$this->name] = true;

        try {
            foreach (array_slice($this->bounds->lower_bounds, 0, $this->bounds->initial_lower_bound_count) as $bound) {
                foreach ($bound->type->getAtomicTypes() as $atomic_type) {
                    if (!$atomic_type instanceof TNever
                        && !($atomic_type instanceof TTypeVariable && $atomic_type->isNeverBound())
                    ) {
                        return false;
                    }
                }
            }
        } finally {
            unset($checking[$this->name]);
        }

        return true;
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return null;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
