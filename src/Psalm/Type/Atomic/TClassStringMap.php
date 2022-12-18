<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Union;

use function get_class;

/**
 * Represents an array where the type of each value
 * is a function of its string key value
 *
 * @psalm-immutable
 */
final class TClassStringMap extends Atomic
{
    /**
     * @var string
     */
    public $param_name;

    public ?TNamedObject $as_type;

    /**
     * @var Union
     */
    public $value_param;

    /**
     * Constructs a new instance of a list
     */
    public function __construct(
        string $param_name,
        ?TNamedObject $as_type,
        Union $value_param,
        bool $from_docblock = false
    ) {
        $this->param_name = $param_name;
        $this->as_type = $as_type;
        $this->value_param = $value_param;
        $this->from_docblock = $from_docblock;
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'class-string-map'
            . '<'
            . $this->param_name
            . ' as '
            . ($this->as_type ? $this->as_type->getId($exact) : 'object')
            . ', '
            . $this->value_param->getId($exact)
            . '>';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($use_phpdoc_format) {
            return (new TArray([Type::getString(), $this->value_param]))
                ->toNamespacedString(
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    true,
                );
        }

        return 'class-string-map'
            . '<'
            . $this->param_name
            . ($this->as_type ? ' as ' . $this->as_type : '')
            . ', '
            . $this->value_param->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                false,
            )
            . '>';
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
        return 'array';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
    }

    /**
     * @psalm-suppress InaccessibleProperty We're only acting on cloned instances
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
        $cloned = null;

        foreach ([Type::getString(), $this->value_param] as $offset => $type_param) {
            $input_type_param = null;

            if ($input_type instanceof TList) {
                $input_type = $input_type->getKeyedArray();
            }

            if (($input_type instanceof TGenericObject
                    || $input_type instanceof TIterable
                    || $input_type instanceof TArray)
                &&
                    isset($input_type->type_params[$offset])
            ) {
                $input_type_param = $input_type->type_params[$offset];
            } elseif ($input_type instanceof TKeyedArray) {
                if ($offset === 0) {
                    if ($input_type->is_list) {
                        continue;
                    }
                    $input_type_param = $input_type->getGenericKeyType();
                } else {
                    $input_type_param = $input_type->getGenericValueType();
                }
            }

            $value_param = TemplateStandinTypeReplacer::replace(
                $type_param,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type_param,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                null,
                $depth + 1,
            );

            if ($offset === 1 && ($cloned || $this->value_param !== $value_param)) {
                $cloned ??= clone $this;
                $cloned->value_param = $value_param;
            }
        }

        return $cloned ?? $this;
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): self {
        $value_param = TemplateInferredTypeReplacer::replace(
            $this->value_param,
            $template_result,
            $codebase,
        );
        if ($value_param === $this->value_param) {
            return $this;
        }
        return new static(
            $this->param_name,
            $this->as_type,
            $value_param,
        );
    }

    protected function getChildNodeKeys(): array
    {
        return ['value_param'];
    }

    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if (!$this->value_param->equals($other_type->value_param, $ensure_source_equality)) {
            return false;
        }

        return true;
    }

    public function getAssertionString(): string
    {
        return $this->getKey();
    }

    public function getStandinKeyParam(): Union
    {
        return new Union([
            new TTemplateParamClass(
                $this->param_name,
                $this->as_type->value ?? 'object',
                $this->as_type,
                'class-string-map',
            ),
        ]);
    }
}
