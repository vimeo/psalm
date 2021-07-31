<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Type\Atomic\TIntRange;

/**
 * @internal
 */
class IntegerRangeComparator
{
    public static function isContainedBy(
        TIntRange $input_type_part,
        TIntRange $container_type_part
    ) : bool {
        $is_input_min = $input_type_part->min_bound === null;
        $is_input_max = $input_type_part->max_bound === null;
        $is_container_min = $container_type_part->min_bound === null;
        $is_container_max = $container_type_part->max_bound === null;

        $is_input_min_in_container = (
                $is_container_min ||
                (!$is_input_min && $container_type_part->min_bound <= $input_type_part->min_bound)
            );
        $is_input_max_in_container = (
                $is_container_max ||
                (!$is_input_max && $container_type_part->max_bound >= $input_type_part->max_bound)
            );
        return $is_input_min_in_container && $is_input_max_in_container;
    }
}
