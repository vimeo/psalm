<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;

/**
 * @internal
 */
class ArrayTypeComparator
{
    /**
     * @param TArray|TKeyedArray|TList|TClassStringMap $input_type_part
     * @param TArray|TKeyedArray|TList|TClassStringMap $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ): bool {
        $result = $input_type_part->containedBy($container_type_part, $codebase);
        if ($result->result) {
            return true;
        }

        if ($atomic_comparison_result !== null) {
            if ($result->result_with_scalar_coercion) {
                $atomic_comparison_result->scalar_type_match_found = true;
            } elseif ($result->result_with_coercion) {
                $atomic_comparison_result->type_coerced = true;
            } elseif ($result->result_with_coercion_from_mixed) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;
            } elseif ($result->result_ignoring_scalar) {
                $atomic_comparison_result->scalar_type_match_found = true;
            } elseif ($result->result_with_to_string_cast) {
                $atomic_comparison_result->to_string_cast = true;
            }

            if ($result->is_less_specific_type) {
                $atomic_comparison_result->type_coerced = true;
            }
        }

        if ($atomic_comparison_result !== null && $atomic_comparison_result->to_string_cast) {
            return true;
        }

        return false;
    }
}
