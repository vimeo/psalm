<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * @internal
 */
class TypeComparisonResult
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

    /** @var non-empty-list<int|string>|null */
    public ?array $missing_shape_fields = null;

    /**
     * @param non-empty-list<int|string>|null $missing_shape_fields
     */
    public function __construct(
        ?bool $scalar_type_match_found = null,
        ?bool $type_coerced = null,
        ?bool $type_coerced_from_mixed = null,
        ?bool $type_coerced_from_as_mixed = null,
        ?bool $to_string_cast = null,
        ?bool $type_coerced_from_scalar = null,
        ?Union $replacement_union_type = null,
        ?Atomic $replacement_atomic_type = null,
        ?array $missing_shape_fields = null
    ) {
        $this->scalar_type_match_found = $scalar_type_match_found;
        $this->type_coerced = $type_coerced;
        $this->type_coerced_from_mixed = $type_coerced_from_mixed;
        $this->type_coerced_from_as_mixed = $type_coerced_from_as_mixed;
        $this->to_string_cast = $to_string_cast;
        $this->type_coerced_from_scalar = $type_coerced_from_scalar;
        $this->replacement_union_type = $replacement_union_type;
        $this->replacement_atomic_type = $replacement_atomic_type;
        $this->missing_shape_fields = $missing_shape_fields;
    }
}
