<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Type\Atomic;
use Psalm\Type\Union;

class TypeComparisonResult
{
    /** @var ?bool */
    public $scalar_type_match_found;

    /** @var ?bool */
    public $type_coerced;

    /** @var ?bool */
    public $type_coerced_from_mixed;

    /** @var ?bool */
    public $type_coerced_from_as_mixed;

    /** @var ?bool */
    public $to_string_cast;

    /** @var ?bool */
    public $type_coerced_from_scalar;

    /** @var ?Union */
    public $replacement_union_type;

    /** @var ?Atomic */
    public $replacement_atomic_type;
}
