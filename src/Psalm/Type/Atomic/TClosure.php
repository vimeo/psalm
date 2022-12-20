<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_merge;

/**
 * Represents a closure where we know the return type and params
 *
 * @psalm-immutable
 */
final class TClosure extends TNamedObject
{
    use CallableTrait;

    /** @var array<string, bool> */
    public $byref_uses = [];

    /**
     * @param list<FunctionLikeParameter> $params
     * @param array<string, bool> $byref_uses
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties> $extra_types
     */
    public function __construct(
        string $value = 'callable',
        ?array $params = null,
        ?Union $return_type = null,
        ?bool $is_pure = null,
        array $byref_uses = [],
        array $extra_types = [],
        bool $from_docblock = false
    ) {
        $this->value = $value;
        $this->params = $params;
        $this->return_type = $return_type;
        $this->is_pure = $is_pure;
        $this->byref_uses = $byref_uses;
        $this->extra_types = $extra_types;
        $this->from_docblock = $from_docblock;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): self {
        $replaced = $this->replaceCallableTemplateTypesWithArgTypes($template_result, $codebase);
        $intersection = $this->replaceIntersectionTemplateTypesWithArgTypes($template_result, $codebase);
        if (!$replaced && !$intersection) {
            return $this;
        }
        return new static(
            $this->value,
            $replaced[0] ?? $this->params,
            $replaced[1] ?? $this->return_type,
            $this->is_pure,
            $this->byref_uses,
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
        $replaced = $this->replaceCallableTemplateTypesWithStandins(
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
        if (!$replaced && !$intersection) {
            return $this;
        }
        return new static(
            $this->value,
            $replaced[0] ?? $this->params,
            $replaced[1] ?? $this->return_type,
            $this->is_pure,
            $this->byref_uses,
            $intersection ?? $this->extra_types,
        );
    }

    protected function getChildNodeKeys(): array
    {
        return array_merge(parent::getChildNodeKeys(), $this->getCallableChildNodeKeys());
    }
}
