<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Issue\CodeIssue;
use Psalm\Type\Union;
use Stringable;

use function array_column;
use function array_fill_keys;
use function array_map;
use function count;
use function implode;

abstract class FunctionLikeStorage implements HasAttributesInterface, Stringable
{
    use CustomMetadataTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    public ?CodeLocation $location = null;

    public ?CodeLocation $stmt_location = null;

    /**
     * @psalm-readonly-allow-private-mutation
     * @var list<FunctionLikeParameter>
     */
    public array $params = [];

    /**
     * @psalm-readonly-allow-private-mutation
     * @var array<string, bool>
     */
    public array $param_lookup = [];

    public ?Union $return_type = null;

    public ?CodeLocation $return_type_location = null;

    public ?Union $signature_return_type = null;

    public ?CodeLocation $signature_return_type_location = null;

    public ?string $cased_name = null;

    /**
     * @var array<int, string>
     */
    public array $suppressed_issues = [];

    public ?bool $deprecated = null;

    /**
     * @var list<non-empty-string>
     */
    public array $internal = [];

    public bool $variadic = false;

    public bool $returns_by_ref = false;

    public ?int $required_param_count = null;

    /**
     * @var array<string, Union>
     */
    public array $defined_constants = [];

    /**
     * @var array<string, bool>
     */
    public array $global_variables = [];

    /**
     * @var array<string, Union>
     */
    public array $global_types = [];

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
    public ?array $template_types = null;

    /**
     * @var array<int, Possibilities>
     */
    public array $assertions = [];

    /**
     * @var array<int, Possibilities>
     */
    public array $if_true_assertions = [];

    /**
     * @var array<int, Possibilities>
     */
    public array $if_false_assertions = [];

    public bool $has_visitor_issues = false;

    /**
     * @var list<CodeIssue>
     */
    public array $docblock_issues = [];

    /**
     * @var array<string, bool>
     */
    public array $throws = [];

    /**
     * @var array<string, CodeLocation>
     */
    public array $throw_locations = [];

    public bool $has_yield = false;

    public bool $mutation_free = false;

    public ?string $return_type_description = null;

    /**
     * @var array<string, CodeLocation>
     */
    public array $unused_docblock_parameters = [];

    public bool $has_undertyped_native_parameters = false;

    public bool $is_static = false;

    public bool $pure = false;

    /**
     * Whether or not the function output is dependent solely on input - a function can be
     * impure but still have this property (e.g. var_export). Useful for taint analysis.
     */
    public bool $specialize_call = false;

    /**
     * @var array<string>
     */
    public array $taint_source_types = [];

    /**
     * @var array<string>
     */
    public array $added_taints = [];

    /**
     * @var array<string>
     */
    public array $removed_taints = [];

    /**
     * @var array<Union>
     */
    public array $conditionally_removed_taints = [];

    /**
     * @var array<int, string>
     */
    public array $return_source_params = [];

    public bool $allow_named_arg_calls = true;

    /**
     * @var list<AttributeStorage>
     */
    public array $attributes = [];

    /**
     * @var list<array{fqn: string, params: array<int>, return: bool}>|null
     */
    public ?array $proxy_calls = [];

    public ?string $description = null;

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

        $visibility_text = match ($this->visibility) {
            ClassLikeAnalyzer::VISIBILITY_PRIVATE => 'private',
            ClassLikeAnalyzer::VISIBILITY_PROTECTED => 'protected',
            default => 'public',
        };

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

        $visibility_text = match ($this->visibility) {
            ClassLikeAnalyzer::VISIBILITY_PRIVATE => 'private',
            ClassLikeAnalyzer::VISIBILITY_PROTECTED => 'protected',
            default => 'public',
        };

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
    public function addParam(FunctionLikeParameter $param, ?bool $lookup_value = null): void
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
}
