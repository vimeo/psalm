<?php
namespace Psalm;

class FunctionLikeParameter
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $by_ref;

    /**
     * @var Type\Union
     */
    public $type;

    /**
     * @var Type\Union
     */
    public $signature_type;

    /**
     * @var bool
     */
    public $is_optional;

    /**
     * @var bool
     */
    public $is_nullable;

    /**
     * @var int
     */
    public $line;

    /**
     * @var bool
     */
    public $is_variadic;

    /**
     * @param string        $name
     * @param boolean       $by_ref
     * @param Type\Union    $type
     * @param boolean       $is_optional
     * @param boolean       $is_nullable
     * @param boolean       $is_variadic
     */
    public function __construct(
        $name,
        $by_ref,
        Type\Union $type,
        $is_optional = true,
        $is_nullable = false,
        $is_variadic = false
    ) {
        $this->name = $name;
        $this->by_ref = $by_ref;
        $this->type = $type;
        $this->signature_type = $type;
        $this->is_optional = $is_optional;
        $this->is_nullable = $is_nullable;
        $this->is_variadic = $is_variadic;
    }
}
