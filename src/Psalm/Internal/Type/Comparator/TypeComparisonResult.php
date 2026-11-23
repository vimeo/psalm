<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Internal\Type\TemplateBound;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_merge;

/**
 * @internal
 */
final class TypeComparisonResult
{
    /**
     * This is used to trigger `InvalidScalarArgument` in situations where we know PHP
     * will try to coerce one scalar type to another.
     */
    public ?bool $scalar_type_match_found = null;

    public ?bool $type_coerced = null;

    public ?bool $type_coerced_from_mixed = null;

    public ?bool $type_coerced_from_as_mixed = null;

    public ?bool $to_string_cast = null;

    /**
     * A callable that takes a parameter by reference was given where it is called with a value for it: it is
     * accepted, and reported where the type is checked (`CallableByReferenceMismatch`).
     */
    public ?bool $callable_by_reference_mismatch = null;

    /**
     * This is primarily used for array access.
     * For example in this function we know that there are only two possible keys, 0 and 1
     * But we allow the array to be addressed by an arbitrary integer $i.
     *
     * function takesAnInt(int $i): string {
     *     return ["foo", "bar"][$i];
     * }
     */
    public ?bool $type_coerced_from_scalar = null;

    public ?Union $replacement_union_type = null;

    public ?Atomic $replacement_atomic_type = null;

    /** @var ?non-empty-list<int|string> */
    public ?array $missing_shape_fields = null;

    /**
     * Lower bounds recorded for type variables encountered in container
     * position: `name >: input`.
     *
     * @var list<array{string, TemplateBound}>
     */
    public array $type_variable_lower_bounds = [];

    /**
     * Upper bounds recorded for type variables encountered in input position:
     * `name <: container`.
     *
     * @var list<array{string, TemplateBound}>
     */
    public array $type_variable_upper_bounds = [];

    /**
     * Takes the type-variable bounds recorded by the comparison of a part (an array value, a shape entry).
     *
     * @psalm-external-mutation-free
     */
    public function addTypeVariableBounds(self $part_comparison_result): void
    {
        $this->type_variable_lower_bounds = array_merge(
            $this->type_variable_lower_bounds,
            $part_comparison_result->type_variable_lower_bounds,
        );

        $this->type_variable_upper_bounds = array_merge(
            $this->type_variable_upper_bounds,
            $part_comparison_result->type_variable_upper_bounds,
        );
    }

    /**
     * Takes the result of a failed comparison of a part (a type param, a key, a value): each of these flags holds
     * only if it holds for every failed part.
     *
     * @psalm-external-mutation-free
     */
    public function mergeFailedPart(self $part_comparison_result): void
    {
        $this->type_coerced = $part_comparison_result->type_coerced === true
            && $this->type_coerced !== false;
        $this->type_coerced_from_mixed = $part_comparison_result->type_coerced_from_mixed === true
            && $this->type_coerced_from_mixed !== false;
        $this->type_coerced_from_as_mixed = $part_comparison_result->type_coerced_from_as_mixed === true
            && $this->type_coerced_from_as_mixed !== false;
        $this->type_coerced_from_scalar = $part_comparison_result->type_coerced_from_scalar === true
            && $this->type_coerced_from_scalar !== false;
        $this->scalar_type_match_found = $part_comparison_result->scalar_type_match_found === true
            && $this->scalar_type_match_found !== false;
    }
}
