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
     * @var boolean
     */
    public $deprecated = false;

    /**
     * Whether or not the function uses get_args
     *
     * @var boolean
     */
    public $variadic = false;

    /**
     * @var array<int, string>
     */
    public $suppress = [];

    /** @var int */
    public $return_type_line_number;
}
