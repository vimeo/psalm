<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Type\Atomic;
use Psalm\Type\Union;

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
}
