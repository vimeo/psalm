<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TDependentGetDebugType;
use Psalm\Type\Atomic\TDependentGetType;
use Psalm\Type\Atomic\TDependentListKey;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\THtmlEscapedString;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;

use function array_values;
use function get_class;
use function strtolower;

/**
 * @internal
 */
class IntegerRangeComparator
{
    public static function isContainedBy(
        TIntRange $input_type_part,
        TIntRange $container_type_part,
    ) : bool {
        $is_input_min = $input_type_part->min_bound === TIntRange::BOUND_MIN;
        $is_input_max = $input_type_part->max_bound === TIntRange::BOUND_MAX;
        $is_container_min = $container_type_part->min_bound === TIntRange::BOUND_MIN;
        $is_container_max = $container_type_part->max_bound === TIntRange::BOUND_MAX;

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
