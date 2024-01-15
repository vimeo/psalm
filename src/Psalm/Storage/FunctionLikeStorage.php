<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Issue\CodeIssue;
use Psalm\Type\Union;

use function array_column;
use function array_fill_keys;
use function array_map;
use function count;
use function implode;

abstract class FunctionLikeStorage implements HasAttributesInterface
{
    use CustomMetadataTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @var CodeLocation|null
     */
    public $location;

    /**
     * @var CodeLocation|null
     */
    public $stmt_location;

    /**
     * @psalm-readonly-allow-private-mutation
     * @var list<FunctionLikeParameter>
     */
    public $params = [];

    /**
     * @psalm-readonly-allow-private-mutation
     * @var array<string, bool>
     */
    public $param_lookup = [];

    /**
     * @var Union|null
     */
    public $return_type;

    /**
     * @var CodeLocation|null
     */
    public $return_type_location;

    /**
     * @var Union|null
     */
    public $signature_return_type;

    /**
     * @var CodeLocation|null
     */
    public $signature_return_type_location;

    /**
     * @var ?string
     */
    public $cased_name;

    /**
     * @var array<int, string>
     */
    public $suppressed_issues = [];

    /**
     * @var ?bool
     */
    public $deprecated;

    /**
     * @var list<non-empty-string>
     */
    public $internal = [];

    /**
     * @var bool
     */
    public $variadic = false;

    /**
     * @var bool
     */
    public $returns_by_ref = false;

    /**
     * @var ?int
     */
    public $required_param_count;

    /**
     * @var array<string, Union>
     */
    public $defined_constants = [];

    /**
     * @var array<string, bool>
     */
    public $global_variables = [];

    /**
     * @var array<string, Union>
     */
    public $global_types = [];

    /**
     * An array holding the class template "as" types.
     *
     * It's the de-facto list of all templates on a given class.
     *
     * The name of the template is the first key. The nested array is keyed by a unique
     * function identifier. This allows operations with the same-named template defined
     * across multiple classes and/or functions to not run into trouble.
     *
     * @var array<string, non-empty-array<string, Union>>|null
     */
    public $template_types;

    /**
     * @var array<int, Possibilities>
     */
    public $assertions = [];

    /**
     * @var array<int, Possibilities>
     */
    public $if_true_assertions = [];

    /**
     * @var array<int, Possibilities>
     */
    public $if_false_assertions = [];

    /**
     * @var bool
     */
    public $has_visitor_issues = false;

    /**
     * @var list<CodeIssue>
     */
    public $docblock_issues = [];

    /**
     * @var array<string, bool>
     */
    public $throws = [];

    /**
     * @var array<string, CodeLocation>
     */
    public $throw_locations = [];

    /**
     * @var bool
     */
    public $has_yield = false;

    /**
     * @var bool
     */
    public $mutation_free = false;

    /**
     * @var string|null
     */
    public $return_type_description;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     * @var array<string, CodeLocation>|null
     * @deprecated will be removed in Psalm 6. use {@see FunctionLikeStorage::$unused_docblock_parameters} instead
     */
    public $unused_docblock_params;

    /**
     * @var array<string, CodeLocation>
     */
    public array $unused_docblock_parameters = [];

    public bool $has_undertyped_native_parameters = false;

    /**
     * @var bool
     */
    public $pure = false;

    /**
     * Whether or not the function output is dependent solely on input - a function can be
     * impure but still have this property (e.g. var_export). Useful for taint analysis.
     *
     * @var bool
     */
    public $specialize_call = false;

    /**
     * @var array<string>
     */
    public $taint_source_types = [];

    /**
     * @var array<string>
     */
    public $added_taints = [];

    /**
     * @var array<string>
     */
    public $removed_taints = [];

    /**
     * @var array<Union>
     */
    public $conditionally_removed_taints = [];

    /**
     * @var array<int, string>
     */
    public $return_source_params = [];

    /**
     * @var bool
     */
    public $allow_named_arg_calls = true;

    /**
     * @var list<AttributeStorage>
     */
    public $attributes = [];

    /**
     * @var list<array{fqn: string, params: array<int>, return: bool}>|null
     */
    public $proxy_calls = [];

    /**
     * @var ?string
     */
    public $description;

    public bool $public_api = false;

    /**
     * Used in the Language Server
     */
    public function getHoverMarkdown(): string
    {
        $params = count($this->params) > 0 ? "\n" . implode(
            ",\n",
            array_map(
                static function (FunctionLikeParameter $param): string {
                    $realType = $param->type ?: 'mixed';
                    return "    {$realType} \${$param->name}";
                },
                $this->params,
            ),
        ) . "\n" : '';
        $return_type = $this->return_type ?: 'mixed';
        $symbol_text = "function {$this->cased_name}({$params}): {$return_type}";

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

    public function getCompletionSignature(): string
    {
        $symbol_text = 'function ' . $this->cased_name . '('   . implode(
            ',',
            array_map(
                static fn(FunctionLikeParameter $param): string => ($param->type ?: 'mixed') . ' $' . $param->name,
                $this->params,
            ),
        ) .  ') : ' . ($this->return_type ?: 'mixed');

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

    /**
     * @internal
     * @param list<FunctionLikeParameter> $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
        $param_names = array_column($params, 'name');
        $this->param_lookup = array_fill_keys($param_names, true);
    }

    /**
     * @internal
     */
    public function addParam(FunctionLikeParameter $param, bool $lookup_value = null): void
    {
        $this->params[] = $param;
        $this->param_lookup[$param->name] = $lookup_value ?? true;
    }

    /**
     * @return list<AttributeStorage>
     */
    public function getAttributeStorages(): array
    {
        return $this->attributes;
    }

    public function __toString(): string
    {
        return $this->getCompletionSignature();
    }

    /**
     * @deprecated will be removed in Psalm 6. use {@see FunctionLikeStorage::getCompletionSignature()} instead
     * @psalm-suppress PossiblyUnusedParam, PossiblyUnusedMethod
     */
    public function getSignature(bool $allow_newlines): string
    {
        return $this->getCompletionSignature();
    }
}
