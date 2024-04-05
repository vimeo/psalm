<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Denotes the `callable` type. Can result from an `is_callable` check.
 *
 * @psalm-immutable
 */
final class TCallable extends Atomic
{
    use CallableTrait;

    /**
     * @var string
     */
    public $value;

    /**
     * Constructs a new instance of a generic type
     *
     * @param list<FunctionLikeParameter> $params
     */
    public function __construct(
        string $value = 'callable',
        ?array $params = null,
        ?Union $return_type = null,
        ?bool $is_pure = null,
        bool $from_docblock = false
    ) {
        $this->value = $value;
        $this->params = $params;
        $this->return_type = $return_type;
        $this->is_pure = $is_pure;
        parent::__construct($from_docblock);
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): string {
        return 'callable';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return $this->params === null && $this->return_type === null;
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithArgTypes(TemplateResult $template_result, ?Codebase $codebase): self
    {
        $replaced = $this->replaceCallableTemplateTypesWithArgTypes($template_result, $codebase);
        if (!$replaced) {
            return $this;
        }
        return new static(
            $this->value,
            $replaced[0],
            $replaced[1],
            $this->is_pure,
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
        if (!$replaced) {
            return $this;
        }
        return new static(
            $this->value,
            $replaced[0],
            $replaced[1],
            $this->is_pure,
        );
    }

    protected function getChildNodeKeys(): array
    {
        return $this->getCallableChildNodeKeys();
    }
}
