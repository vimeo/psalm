<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use function array_filter;
use function array_slice;
use function count;

/**
 * Bounds accumulated for a type variable: constraints recorded while a
 * TTypeVariable flows through a function body, reconciled against each other
 * when the surrounding function-like has been analyzed.
 *
 * @internal
 * @psalm-mutable
 */
final class TypeVariableBounds
{
    /**
     * How many of the lower bounds were inferred at the construction site
     * (the rest were recorded by later uses).
     */
    public int $initial_lower_bound_count;

    /**
     * How many of the upper bounds were set at the construction site.
     */
    public int $initial_upper_bound_count;

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
        $this->initial_upper_bound_count = count($upper_bounds);
    }

    /**
     * Adds the bounds another analysis of the construction site inferred.
     *
     * @param list<TemplateBound> $lower_bounds
     * @param list<TemplateBound> $upper_bounds
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function addInitialBounds(array $lower_bounds, array $upper_bounds): void
    {
        $this->lower_bounds = [
            ...array_slice($this->lower_bounds, 0, $this->initial_lower_bound_count),
            ...$lower_bounds,
            ...array_slice($this->lower_bounds, $this->initial_lower_bound_count),
        ];
        $this->upper_bounds = [
            ...array_slice($this->upper_bounds, 0, $this->initial_upper_bound_count),
            ...$upper_bounds,
            ...array_slice($this->upper_bounds, $this->initial_upper_bound_count),
        ];
        $this->initial_lower_bound_count += count($lower_bounds);
        $this->initial_upper_bound_count += count($upper_bounds);
    }

    /**
     * Keeps only the bounds the callback accepts.
     *
     * @param pure-callable(TemplateBound): bool $is_kept
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function filterBounds(callable $is_kept): void
    {
        $initial_lower_bounds = array_filter(
            array_slice($this->lower_bounds, 0, $this->initial_lower_bound_count),
            $is_kept,
        );
        $initial_upper_bounds = array_filter(
            array_slice($this->upper_bounds, 0, $this->initial_upper_bound_count),
            $is_kept,
        );

        $this->lower_bounds = [
            ...$initial_lower_bounds,
            ...array_filter(array_slice($this->lower_bounds, $this->initial_lower_bound_count), $is_kept),
        ];
        $this->upper_bounds = [
            ...$initial_upper_bounds,
            ...array_filter(array_slice($this->upper_bounds, $this->initial_upper_bound_count), $is_kept),
        ];
        $this->initial_lower_bound_count = count($initial_lower_bounds);
        $this->initial_upper_bound_count = count($initial_upper_bounds);
    }
}
