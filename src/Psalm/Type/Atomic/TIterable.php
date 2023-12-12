<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function count;
use function implode;
use function substr;

/**
 * denotes the `iterable` type(which can also result from an `is_iterable` check).
 *
 * @psalm-immutable
 */
final class TIterable extends Atomic
{
    use HasIntersectionTrait;
    /**
     * @use GenericTrait<array{Union, Union}>
     */
    use GenericTrait;

    /**
     * @var array{Union, Union}
     */
    public array $type_params;

    /**
     * @var string
     */
    public $value = 'iterable';

    /**
     * @var bool
     */
    public $has_docblock_params = false;

    /**
     * @param array{Union, Union}|array<never, never> $type_params
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     */
    public function __construct(array $type_params = [], array $extra_types = [], bool $from_docblock = false)
    {
        if (isset($type_params[0], $type_params[1])) {
            $this->has_docblock_params = true;
            $this->type_params = $type_params;
        } else {
            $this->type_params = [Type::getMixed(), Type::getMixed()];
        }
        $this->extra_types = $extra_types;
        parent::__construct($from_docblock);
    }

    public function getKey(bool $include_extra = true): string
    {
        if ($include_extra && $this->extra_types) {
            // do nothing
        }

        return 'iterable';
    }

    public function getAssertionString(): string
    {
        return 'iterable';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        $s = '';
        foreach ($this->type_params as $type_param) {
            $s .= $type_param->getId($exact) . ', ';
        }

        $extra_types = '';

        if ($this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
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
        return $analysis_php_version_id >= 7_01_00 ? 'iterable' : null;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return $this->type_params[0]->isMixed() && $this->type_params[1]->isMixed();
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
            if (!$type_param->equals($other_type->type_params[$i], $ensure_source_equality, false)) {
                return false;
            }
        }

        return true;
    }

    protected function getChildNodeKeys(): array
    {
        return ['type_params', 'extra_types'];
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithArgTypes(TemplateResult $template_result, ?Codebase $codebase): self
    {
        $type_params = $this->replaceTypeParamsTemplateTypesWithArgTypes(
            $template_result,
            $codebase,
        );
        $intersection = $this->replaceIntersectionTemplateTypesWithArgTypes(
            $template_result,
            $codebase,
        );
        return new static(
            $type_params ?? $this->type_params,
            $intersection ?? $this->extra_types,
        );
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0
    ): self {
        $types = $this->replaceTypeParamsTemplateTypesWithStandins(
            $template_result,
            $codebase,
            $statements_analyzer,
            $input_type,
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_lower_bound,
            $depth,
        );
        $intersection = $this->replaceIntersectionTemplateTypesWithStandins(
            $template_result,
            $codebase,
            $statements_analyzer,
            $input_type,
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_lower_bound,
            $depth,
        );
        if (!$types && !$intersection) {
            return $this;
        }
        return new static(
            $types ?? $this->type_params,
            $intersection ?? $this->extra_types,
        );
    }
}
