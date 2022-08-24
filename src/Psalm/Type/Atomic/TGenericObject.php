<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_merge;
use function count;
use function implode;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Denotes an object type that has generic parameters e.g. `ArrayObject<string, Foo\Bar>`
 */
final class TGenericObject extends TNamedObject
{
    /**
     * @use GenericTrait<non-empty-list<Union>>
     */
    use GenericTrait;

    /** @var bool if the parameters have been remapped to another class */
    public $remapped_params = false;

    /**
     * @param string                $value the name of the object
     * @param non-empty-list<Union> $type_params
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties>|null $extra_types
     */
    public function __construct(string $value, array $type_params, bool $remapped_params = false, bool $is_static = false, ?array $extra_types = null)
    {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }

        $this->value = $value;
        $this->type_params = $type_params;
        $this->remapped_params = $remapped_params;
        $this->is_static = $is_static;
        $this->extra_types = $extra_types;
    }

    public function getKey(bool $include_extra = true): string
    {
        $s = '';

        foreach ($this->type_params as $type_param) {
            $s .= $type_param->getKey() . ', ';
        }

        $extra_types = '';

        if ($include_extra && $this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): ?string {
        $result = $this->toNamespacedString($namespace, $aliased_classes, $this_class, true);
        $intersection = strrpos($result, '&');
        if ($intersection === false || $analysis_php_version_id >= 8_01_00) {
            return $result;
        }
        return substr($result, $intersection+1);
    }

    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (!$other_type instanceof self) {
            return false;
        }

        if (count($this->type_params) !== count($other_type->type_params)) {
            return false;
        }

        foreach ($this->type_params as $i => $type_param) {
            if (!$type_param->equals($other_type->type_params[$i], $ensure_source_equality)) {
                return false;
            }
        }

        return true;
    }

    public function getAssertionString(): string
    {
        return $this->value;
    }

    public function getChildNodes(): array
    {
        return array_merge($this->type_params, $this->extra_types ?? []);
    }

    public function replaceClassLike(string $old, string $new): static
    {
        return new static(
            strtolower($this->value) === $old ? $new : $this->value,
            $this->replaceTypeParamsClassLike($old, $new),
            $this->remapped_params,
            $this->is_static,
            $this->replaceIntersectionClassLike($old, $new)
        );
    }

    public function replaceTemplateTypesWithStandins(TemplateResult $template_result, Codebase $codebase, ?StatementsAnalyzer $statements_analyzer = null, ?Atomic $input_type = null, ?int $input_arg_offset = null, ?string $calling_class = null, ?string $calling_function = null, bool $replace = true, bool $add_lower_bound = false, int $depth = 0): static
    {
        return new static(
            $this->value,
            $this->replaceTypeParamsTemplateTypesWithStandins(
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                $depth
            ),
            $this->remapped_params,
            $this->is_static,
            $this->replaceIntersectionTemplateTypesWithStandins(
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                $depth
            )
        );
    }

    public function replaceTemplateTypesWithArgTypes(TemplateResult $template_result, ?Codebase $codebase): static
    {
        return new static(
            $this->value,
            $this->replaceTypeParamsTemplateTypesWithArgTypes(
                $template_result,
                $codebase
            ),
            true,
            $this->is_static,
            $this->replaceIntersectionTemplateTypesWithArgTypes(
                $template_result,
                $codebase
            )
        );
    }
}
