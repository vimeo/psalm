<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function count;
use function get_class;

/**
 * Denotes a simple array of the form `array<TKey, TValue>`. It expects an array with two elements, both union types.
 *
 * @psalm-immutable
 */
class TArray extends Atomic
{
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
    public $value = 'array';

    /**
     * Constructs a new instance of a generic type
     *
     * @param array{Union, Union} $type_params
     */
    public function __construct(array $type_params, bool $from_docblock = false)
    {
        $this->type_params = $type_params;
        parent::__construct($from_docblock);
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
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
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return $this->type_params[0]->isArrayKey() && $this->type_params[1]->isMixed();
    }

    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if ($this instanceof TNonEmptyArray
            && $other_type instanceof TNonEmptyArray
            && $this->count !== $other_type->count
        ) {
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

    public function getAssertionString(): string
    {
        if ($this->type_params[0]->isMixed() && $this->type_params[1]->isMixed()) {
            return 'array';
        }

        return $this->getId();
    }

    public function isEmptyArray(): bool
    {
        return $this->type_params[1]->isNever();
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
        $type_params = $this->replaceTypeParamsTemplateTypesWithStandins(
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
        if ($type_params) {
            $cloned = clone $this;
            $cloned->type_params = $type_params;
            return $cloned;
        }
        return $this;
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
        if ($type_params) {
            $cloned = clone $this;
            $cloned->type_params = $type_params;
            return $cloned;
        }
        return $this;
    }

    protected function getChildNodeKeys(): array
    {
        return ['type_params'];
    }
}
