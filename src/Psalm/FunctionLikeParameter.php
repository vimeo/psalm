<?php

namespace Psalm;

class FunctionLikeParameter
{
    /** @var string */
    public $name;

    /** @var bool */
    public $by_ref;

    /** @var Type\Union */
    public $type;

    /** @var Type\Union */
    public $signature_type;

    /** @var bool */
    public $is_optional;

    /** @var bool */
    public $is_nullable;

    /** @var int */
    public $line;

    /**
     * @param string        $name
     * @param boolean       $by_ref
     * @param Type\Union    $type
     * @param boolean       $is_optional
     * @param boolean       $is_nullable
     * @param int           $line_number
     */
    public function __construct($name, $by_ref, Type\Union $type, $is_optional = true, $is_nullable = false, $line_number = -1)
    {
        $this->name = $name;
        $this->by_ref = $by_ref;
        $this->type = $type;
        $this->signature_type = $type;
        $this->is_optional = $is_optional;
        $this->is_nullable = $is_nullable;
    }
}
