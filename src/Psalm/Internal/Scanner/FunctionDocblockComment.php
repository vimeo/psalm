<?php
namespace Psalm\Internal\Scanner;

/**
 * @internal
 */
class FunctionDocblockComment
{
    /**
     * @var string|null
     */
    public $return_type = null;

    /**
     * @var string|null
     */
    public $return_type_description = null;

    /**
     * @var array<int, array{name:string, type:string, line_number: int}>
     */
    public $params = [];

    /**
     * @var array<int, array{name:string, type:string, line_number: int}>
     */
    public $globals = [];

    /**
     * Whether or not the function is deprecated
     *
     * @var bool
     */
    public $deprecated = false;

    /**
     * Whether or not the function is internal
     *
     * @var bool
     */
    public $internal = false;

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
     * Whether or not to ignore the nullability of this function's return type
     *
     * @var bool
     */
    public $ignore_falsable_return = false;

    /**
     * @var array<int, string>
     */
    public $suppress = [];

    /**
     * @var array<int, string>
     */
    public $throws = [];

    /** @var int */
    public $return_type_line_number;

    /**
     * @var array<int, array<int, string>>
     */
    public $template_type_names = [];

    /**
     * @var array<int, array{template_type: string, param_name: string, line_number?: int}>
     */
    public $template_typeofs = [];

    /**
     * @var array<int, array{type: string, param_name: string}>
     */
    public $assertions = [];

    /**
     * @var array<int, array{type: string, param_name: string}>
     */
    public $if_true_assertions = [];

    /**
     * @var array<int, array{type: string, param_name: string}>
     */
    public $if_false_assertions = [];
}
