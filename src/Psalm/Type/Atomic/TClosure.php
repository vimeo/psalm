<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function strtolower;

/**
 * Represents a closure where we know the return type and params
 */
final class TClosure extends TNamedObject
{
    use CallableTrait;

    /** @var array<string, bool> */
    public $byref_uses = [];

    /**
     * @param list<FunctionLikeParameter> $params
     * @param array<string, bool> $byref_uses
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties>|null $extra_types
     */
    public function __construct(
        string $value = 'callable',
        ?array $params = null,
        ?Union $return_type = null,
        ?bool $is_pure = null,
        array $byref_uses = [],
        ?array $extra_types = []
    ) {
        $this->value = $value;
        $this->params = $params;
        $this->return_type = $return_type;
        $this->is_pure = $is_pure;
        $this->byref_uses = $byref_uses;
        $this->extra_types = $extra_types;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function replaceClassLike(string $old, string $new): static
    {
        $replaced = $this->replaceCallableClassLike($old, $new);
        return new static(
            strtolower($this->value) === $old ? $new : $this->value,
            $replaced[0] ?? $this->params,
            $replaced[1] ?? $this->return_type,
            $this->is_pure,
            $this->byref_uses,
            $this->replaceIntersectionClassLike($old, $new)
        );
    }


    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): static {
        $replaced = $this->replaceCallableTemplateTypesWithArgTypes($template_result, $codebase);
        return new static(
            $this->value,
            $replaced[0] ?? $this->params,
            $replaced[1] ?? $this->return_type,
            $this->is_pure,
            $this->byref_uses,
            $this->replaceIntersectionTemplateTypesWithArgTypes($template_result, $codebase)
        );
    }

    public function replaceTemplateTypesWithStandins(TemplateResult $template_result, Codebase $codebase, ?StatementsAnalyzer $statements_analyzer = null, ?Atomic $input_type = null, ?int $input_arg_offset = null, ?string $calling_class = null, ?string $calling_function = null, bool $replace = true, bool $add_lower_bound = false, int $depth = 0): static
    {
        $replaced = $this->replaceCallableTemplateTypesWithStandins($template_result, $codebase, $statements_analyzer, $input_type, $input_arg_offset, $calling_class, $calling_function, $replace, $add_lower_bound, $depth);
        return new static(
            $this->value,
            $replaced[0] ?? $this->params,
            $replaced[1] ?? $this->return_type,
            $this->is_pure,
            $this->byref_uses,
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

    public function getChildNodes(): array
    {
        return array_merge(parent::getChildNodes(), $this->getCallableChildNodes());
    }
}
