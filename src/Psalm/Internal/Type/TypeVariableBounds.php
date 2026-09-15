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
     */
    public function __construct(
        public array $lower_bounds = [],
        public array $upper_bounds = [],
    ) {
        $this->initial_lower_bound_count = count($lower_bounds);
    }
}
