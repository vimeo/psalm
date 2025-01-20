<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Represents a closure where we know the return type and params
 *
 * @psalm-immutable
 */
final class TClosure extends TNamedObject
{
    use CallableTrait;

    /**
     * @param list<FunctionLikeParameter> $params
     * @param array<string, bool> $byref_uses
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     */
    public function __construct(
        string $value = 'callable',
        ?array $params = null,
        ?Union $return_type = null,
        ?bool $is_pure = null,
        public array $byref_uses = [],
        array $extra_types = [],
        bool $from_docblock = false,
    ) {
        $this->params = $params;
        $this->return_type = $return_type;
        $this->is_pure = $is_pure;
        parent::__construct(
            $value,
            false,
            false,
            $extra_types,
            $from_docblock,
        );
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        // it can, if it's just 'Closure'
        return $this->params === null && $this->return_type === null && $this->is_pure === null;
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase,
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
        int $depth = 0,
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
        return [...parent::getChildNodeKeys(), ...$this->getCallableChildNodeKeys()];
    }
}
