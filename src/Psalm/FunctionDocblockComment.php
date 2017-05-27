<?php
namespace Psalm;

class FunctionDocblockComment
{
    /**
     * @var string|null
     */
    public $return_type = null;

    /**
     * @var array<int, array{name:string, type:string}>
     */
    public $params = [];

    /**
     * Whether or not the function is deprecated
     *
     * @var bool
     */
    public $deprecated = false;

    /**
     * Whether or not the function uses get_args
     *
     * @var bool
     */
    public $variadic = false;

    /**
     * Whether or not to ignore the nullability of this function's return type
     *
     * @var bool
     */
    public $ignore_nullable_return = false;

    /**
     * @var array<int, string>
     */
    public $suppress = [];

    /** @var int */
    public $return_type_line_number;

    /**
     * @var array<int, array<int, string>>
     */
    public $template_types = [];

    /**
     * @var array<int, array{template_type: string, param_name: string}>
     */
    public $template_typeofs = [];
}
