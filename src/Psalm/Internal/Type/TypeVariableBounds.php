<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use function count;

/**
 * Bounds accumulated for a type variable: constraints recorded while a
 * TTypeVariable flows through a function body, reconciled against each other
 * when the surrounding function-like has been analyzed.
 *
 * @internal
 * @psalm-suppress MissingImmutableAnnotation TypeVariableTracker::addBounds()
 *     appends to lower_bounds/upper_bounds in place, on purpose: every
 *     TTypeVariable holding a reference to this object needs to see later
 *     constraints as they're recorded during analysis.
 */
final class TypeVariableBounds
{
    /**
     * How many of the lower bounds were inferred at the construction site
     * (the rest were recorded by later uses).
     */
    public readonly int $initial_lower_bound_count;

    /**
     * @param list<TemplateBound> $lower_bounds
     * @param list<TemplateBound> $upper_bounds
     * @psalm-mutation-free
     */
    public function __construct(
        public array $lower_bounds = [],
        public array $upper_bounds = [],
    ) {
        $this->initial_lower_bound_count = count($lower_bounds);
    }
}
