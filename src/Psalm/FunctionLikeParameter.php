<?php

namespace Psalm;

class FunctionLikeParameter
{
    /** @var string */
    public $name;

    /** @var string */
    public $by_ref;

    /** @var Type\Union */
    public $type;

    /** @var bool */
    public $is_optional;

    /** @var bool */
    public $is_nullable;

    public function __construct($name, $by_ref, $type, $is_optional = false, $is_nullable = false)
    {
        $this->name = $name;
        $this->by_ref = $by_ref;
        $this->type = $type;
        $this->is_optional = $is_optional;
        $this->is_nullable = $is_nullable;
    }
}
