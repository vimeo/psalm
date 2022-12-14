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
     *
     * @var ?bool
     */
    public ?bool $scalar_type_match_found = null;

    /** @var ?bool */
    public ?bool $type_coerced = null;

    /** @var ?bool */
    public ?bool $type_coerced_from_mixed = null;

    /** @var ?bool */
    public ?bool $type_coerced_from_as_mixed = null;

    /** @var ?bool */
    public ?bool $to_string_cast = null;

    /**
     * This is primarily used for array access.
     * For example in this function we know that there are only two possible keys, 0 and 1
     * But we allow the array to be addressed by an arbitrary integer $i.
     *
     * function takesAnInt(int $i): string {
     *     return ["foo", "bar"][$i];
     * }
     *
     * @var ?bool
     */
    public ?bool $type_coerced_from_scalar = null;

    /** @var ?Union */
    public ?Union $replacement_union_type = null;

    /** @var ?Atomic */
    public ?Atomic $replacement_atomic_type = null;

    /** @var ?non-empty-list<int|string> */
    public ?array $missing_shape_fields = null;
}
