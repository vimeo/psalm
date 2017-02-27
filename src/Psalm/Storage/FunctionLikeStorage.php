<?php
namespace Psalm\Storage;

use PhpParser;
use Psalm\Type;
use Psalm\CodeLocation;
use Psalm\FunctionLikeParameter;

class FunctionLikeStorage
{
    /**
     * @var CodeLocation|null
     */
    public $location;

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

    /**
     * @var array<string, Type\Union>
     */
    public $defined_constants = [];

    /**
     * @var array<string, string>|null
     */
    public $template_types;

    /**
     * @var array<int, string>|null
     */
    public $template_typeof_params;

    /**
     * @var bool
     */
    public $has_template_return_type;

    /**
     * @var array<int, CodeLocation>|null
     */
    public $referencing_locations;
}
