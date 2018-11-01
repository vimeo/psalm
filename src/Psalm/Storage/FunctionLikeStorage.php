<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\Checker\ClassLikeChecker;

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
     * @var array<string, Type\Union|null>
     */
    public $param_types = [];

    /**
     * @var Type\Union|null
     */
    public $return_type;

    /**
     * @var CodeLocation|null
     */
    public $return_type_location;

    /**
     * @var Type\Union|null
     */
    public $signature_return_type;

    /**
     * @var CodeLocation|null
     */
    public $signature_return_type_location;

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
     * @var bool
     */
    public $returns_by_ref = false;

    /**
     * @var int
     */
    public $required_param_count;

    /**
     * @var array<string, Type\Union>
     */
    public $defined_constants = [];

    /**
     * @var array<string, bool>
     */
    public $global_variables = [];

    /**
     * @var array<string, Type\Union>
     */
    public $global_types = [];

    /**
     * @var array<string, Type\Union>|null
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
     * @var array<string, array<int, CodeLocation>>|null
     */
    public $referencing_locations;

    /**
     * @var array<int, Assertion>
     */
    public $assertions = [];

    /**
     * @var array<int, Assertion>
     */
    public $if_true_assertions = [];

    /**
     * @var array<int, Assertion>
     */
    public $if_false_assertions = [];

    /**
     * @var bool
     */
    public $has_visitor_issues = false;

    /**
     * @var bool
     */
    public $has_docblock_issues = false;

    /**
     * @var array<string, bool>
     */
    public $throws = [];

    /**
     * @var bool
     */
    public $has_yield = false;

    /**
     * @var string|null
     */
    public $return_type_description;

    public function __toString()
    {
        $symbol_text = 'function ' . $this->cased_name . '(' . implode(
            ', ',
            array_map(
                function (FunctionLikeParameter $param) : string {
                    return ($param->type ?: 'mixed') . ' $' . $param->name;
                },
                $this->params
            )
        ) . ') : ' . ($this->return_type ?: 'mixed');

        if (!$this instanceof MethodStorage) {
            return $symbol_text;
        }

        switch ($this->visibility) {
            case ClassLikeChecker::VISIBILITY_PRIVATE:
                $visibility_text = 'private';
                break;

            case ClassLikeChecker::VISIBILITY_PROTECTED:
                $visibility_text = 'protected';
                break;

            default:
                $visibility_text = 'public';
        }

        return $visibility_text . ' ' . $symbol_text;
    }
}
