<?php

namespace Psalm\Internal\Analyzer;

class TypeComparisonResult
{
    /** @var ?bool */
    public $scalar_type_match_found = null;

    /** @var ?bool */
    public $type_coerced = null;

    /** @var ?bool */
    public $type_coerced_from_mixed = null;

    /** @var ?bool */
    public $to_string_cast = null;

    /** @var ?bool */
    public $type_coerced_from_scalar = null;
}
