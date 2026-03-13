<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\Mutations;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function count;
use function implode;

/**
 * @psalm-immutable
 */
trait CallableTrait
{
    /**
     * @var list<FunctionLikeParameter>|null
     */
    public ?array $params = [];

    public ?Union $return_type = null;

    /** @var Mutations::LEVEL_* */
    public int $allowed_mutations = Mutations::LEVEL_ALL;


    /**
     * @param list<FunctionLikeParameter>|null $params
     * @return static
     */
    public function replace(?array $params, ?Union $return_type): self
    {
        if ($this->params === $params && $this->return_type === $return_type) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->params = $params;
        $cloned->return_type = $return_type;
        return $cloned;
    }
    /**
     * @param Mutations::LEVEL_* $allowed_mutations
     * @return static
     */
    public function setAllowedMutations(int $allowed_mutations): self
    {
        if ($this->allowed_mutations === $allowed_mutations) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->allowed_mutations = $allowed_mutations;
        return $cloned;
    }

    public function getParamString(): string
    {
        $param_string = '';
        if ($this->params !== null) {
            $param_string .= '(';
            foreach ($this->params as $i => $param) {
                if ($i) {
                    $param_string .= ', ';
                }

                $param_string .= $param->getId();
            }

            $param_string .= ')';
        }

        return $param_string;
    }

    public function getReturnTypeString(): string
    {
        $return_type_string = '';

        if ($this->return_type !== null) {
            $return_type_multiple = count($this->return_type->getAtomicTypes()) > 1;
            $return_type_string = ':' . ($return_type_multiple ? '(' : '')
                . $this->return_type->getId() . ($return_type_multiple ? ')' : '');
        }

        return $return_type_string;
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        $param_string = $this->getParamString();
        $return_type_string = $this->getReturnTypeString();

        $prefix = match ($this->allowed_mutations) {
            Mutations::LEVEL_NONE => 'pure-',
            Mutations::LEVEL_INTERNAL_READ => 'self-accessing-',
            Mutations::LEVEL_INTERNAL_READ_WRITE => 'self-mutating-',
            Mutations::LEVEL_EXTERNAL => 'impure-',
        };

        return $prefix . $this->value . $param_string . $return_type_string;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        if ($use_phpdoc_format) {
            return $this->value;
        }

        $prefix = match ($this->allowed_mutations) {
            Mutations::LEVEL_NONE => 'pure-',
            Mutations::LEVEL_INTERNAL_READ => 'self-accessing-',
            Mutations::LEVEL_INTERNAL_READ_WRITE => 'self-mutating-',
            Mutations::LEVEL_EXTERNAL => 'impure-',
        };

        $param_string = '';
        $return_type_string = '';

        if ($this->params !== null) {
            $params_array = [];

            foreach ($this->params as $param) {
                if (!$param->type) {
                    $type_string = 'mixed';
                } else {
                    $type_string = $param->type->toNamespacedString($namespace, $aliased_classes, $this_class, false);
                }

                $params_array[] = ($param->is_variadic ? '...' : '') . $type_string . ($param->is_optional ? '=' : '');
            }

            $param_string = '(' . implode(', ', $params_array) . ')';
        }

        if ($this->return_type !== null) {
            $return_type_multiple = count($this->return_type->getAtomicTypes()) > 1;

            $return_type_string = ':' . ($return_type_multiple ? '(' : '') . $this->return_type->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                false,
            ) . ($return_type_multiple ? ')' : '');
        }

        return $prefix . $this->value . $param_string . $return_type_string;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): string {
        if ($this instanceof TNamedObject) {
            return parent::toNamespacedString($namespace, $aliased_classes, $this_class, true);
        }

        return $this->value;
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        $param_string = '';
        $return_type_string = '';

        if ($this->params !== null) {
            $param_string .= '(';
            foreach ($this->params as $i => $param) {
                if ($i) {
                    $param_string .= ', ';
                }

                $param_string .= $param->getId();
            }

            $param_string .= ')';
        }

        if ($this->return_type !== null) {
            $return_type_multiple = count($this->return_type->getAtomicTypes()) > 1;
            $return_type_string = ':' . ($return_type_multiple ? '(' : '')
                . $this->return_type->getId($exact) . ($return_type_multiple ? ')' : '');
        }

        $prefix = match ($this->allowed_mutations) {
            Mutations::LEVEL_NONE => 'pure-',
            Mutations::LEVEL_INTERNAL_READ => 'self-accessing-',
            Mutations::LEVEL_INTERNAL_READ_WRITE => 'self-mutating-',
            Mutations::LEVEL_EXTERNAL => 'impure-',
        };
        return $prefix
            . $this->value . $param_string . $return_type_string;
    }

    /**
     * @return array{list<FunctionLikeParameter>|null, Union|null}|null
     */
    protected function replaceCallableTemplateTypesWithStandins(
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
    ): ?array {
        $replaced = false;
        $params = $this->params;
        if ($params) {
            foreach ($params as $offset => $param) {
                if (!$param->type) {
                    continue;
                }

                $input_param_type = null;

                if (($input_type instanceof TClosure || $input_type instanceof TCallable)
                    && isset($input_type->params[$offset])
                ) {
                    $input_param_type = $input_type->params[$offset]->type;
                }

                $new_param = $param->setType(TemplateStandinTypeReplacer::replace(
                    $param->type,
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    $input_param_type,
                    $input_arg_offset,
                    $calling_class,
                    $calling_function,
                    $replace,
                    !$add_lower_bound,
                    null,
                    $depth,
                ));
                $replaced = $replaced || $new_param !== $param;
                $params[$offset] = $new_param;
            }
        }

        $return_type = $this->return_type;
        if ($return_type) {
            $return_type = TemplateStandinTypeReplacer::replace(
                $return_type,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type instanceof TCallable || $input_type instanceof TClosure
                    ? $input_type->return_type
                    : null,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
            );
            $replaced = $replaced || $this->return_type !== $return_type;
        }

        if ($replaced) {
            return [$params, $return_type];
        }
        return null;
    }


    /**
     * @return array{list<FunctionLikeParameter>|null, Union|null}|null
     */
    protected function replaceCallableTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): ?array {
        $replaced = false;

        $params = $this->params;
        if ($params) {
            foreach ($params as $k => $param) {
                if ($param->type) {
                    $new_param = $param->setType(TemplateInferredTypeReplacer::replace(
                        $param->type,
                        $template_result,
                        $codebase,
                    ));
                    $replaced = $replaced || $new_param !== $param;
                    $params[$k] = $new_param;
                }
            }
        }

        $return_type = $this->return_type;
        if ($return_type) {
            $return_type = TemplateInferredTypeReplacer::replace(
                $return_type,
                $template_result,
                $codebase,
            );
            $replaced = $replaced || $return_type !== $this->return_type;
        }
        if ($replaced) {
            return [$params, $return_type];
        }
        return null;
    }

    /**
     * @return list<string>
     * @psalm-pure
     */
    protected function getCallableChildNodeKeys(): array
    {
        return ['params', 'return_type'];
    }
}
