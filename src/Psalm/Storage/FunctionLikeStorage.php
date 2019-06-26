<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use const PHP_EOL;
use function implode;
use function array_map;

class FunctionLikeStorage
{
    use CustomMetadataTrait;

    /**
     * @var CodeLocation|null
     */
    public $location;

    /**
     * @var CodeLocation|null
     */
    public $stmt_location;

    /**
     * @var array<int, FunctionLikeParameter>
     */
    public $params = [];

    /**
     * @var array<string, Type\Union|null>
     */
    public $param_types = [];

    /**
     * @var array<int, Type\Union|null>
     */
    public $param_out_types = [];

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
    public $internal;

    /**
     * @var null|string
     */
    public $psalm_internal;

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
     * @var array<string, array<string, array{Type\Union}>>|null
     */
    public $template_types;

    /**
     * @var array<int, bool>|null
     */
    public $template_covariants;

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

    /**
     * @var array<string, CodeLocation>|null
     */
    public $unused_docblock_params;

    public function __toString()
    {
        return $this->getSignature(false);
    }

    public function getSignature(bool $allow_newlines = false): string
    {
        $newlines = $allow_newlines && !empty($this->params);

        $symbol_text = 'function ' . $this->cased_name . '(' . ($newlines ? PHP_EOL : '') . implode(
            ',' . ($newlines ? PHP_EOL : ' '),
            array_map(
                function (FunctionLikeParameter $param) use ($newlines) : string {
                    return ($newlines ? '    ' : '') . ($param->type ?: 'mixed') . ' $' . $param->name;
                },
                $this->params
            )
        ) . ($newlines ? PHP_EOL : '') . ') : ' . ($this->return_type ?: 'mixed');

        if (!$this instanceof MethodStorage) {
            return $symbol_text;
        }

        switch ($this->visibility) {
            case ClassLikeAnalyzer::VISIBILITY_PRIVATE:
                $visibility_text = 'private';
                break;

            case ClassLikeAnalyzer::VISIBILITY_PROTECTED:
                $visibility_text = 'protected';
                break;

            default:
                $visibility_text = 'public';
        }

        return $visibility_text . ' ' . $symbol_text;
    }
}
