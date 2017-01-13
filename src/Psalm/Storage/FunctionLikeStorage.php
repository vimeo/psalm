<?php
namespace Psalm\Storage;

use PhpParser;
use Psalm\Type;
use Psalm\CodeLocation;
use Psalm\FunctionLikeParameter;

class FunctionLikeStorage
{
    /**
     * @var string
     */
    public $file_name;

    /**
     * @var array<int, FunctionLikeParameter>
     */
    public $params = [];

    /**
     * @var array<string, Type\Union>
     */
    public $param_types = [];

    /**
     * @var string
     */
    public $namespace;

    /**
     * @var Type\Union|null
     */
    public $return_type;

    /**
     * @var CodeLocation|null
     */
    public $return_type_location;

    /**
     * @var string
     */
    public $cased_name;

    /**
     * @var array<int, string>
     */
    public $suppressed_issues = [];

    /**
     * @var bool
     */
    public $deprecated;

    /**
     * @var bool
     */
    public $variadic;

    /**
     * @var int
     */
    public $required_param_count;
}
