<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function count;
use function implode;

/**
 * @psalm-immutable
 * @api
 */
trait CallableTrait
{
    /**
     * @var list<FunctionLikeParameter>|null
     */
    public ?array $params = [];

    public ?Union $return_type = null;

    /**
     * The purity of the callable: a capability set ({@see TCapabilities}), or a purity template
     * parameter ({@see TTemplateParam} bound by `@psalm-purity-template`) that is resolved from
     * the closure actually passed at each call site.
     */
    public Union $purity;


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
     * @return static
     */
    public function setPurity(int|Union $purity): self
    {
        $purity = self::purityFrom($purity);
        if ($this->purity === $purity || $this->purity->getId() === $purity->getId()) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->purity = $purity;
        return $cloned;
    }

    /**
     * The capabilities this callable needs from its caller. An unresolved purity template
     * counts as its upper bound (any capability): use {@see CallPurityResolver} where the
     * enclosing function-like's own purity templates must be taken into account.
     *
     * @psalm-mutation-free
     */
    public function getCapabilities(): int
    {
        return Capabilities::fromType($this->purity);
    }

    /**
     * Whether the purity is fixed (no purity template involved).
     *
     * @psalm-mutation-free
     */
    public function hasFixedPurity(): bool
    {
        foreach ($this->purity->getAtomicTypes() as $atomic) {
            if (!$atomic instanceof TCapabilities) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-pure
     */
    public static function purityFrom(int|Union $purity): Union
    {
        if ($purity instanceof Union) {
            return $purity;
        }

        return new Union([new TCapabilities($purity)]);
    }

    /**
     * The `pure-`/`impure-` prefix or `<...>` purity suffix of the callable keyword.
     *
     * @psalm-mutation-free
     */
    private function getPurityString(): string
    {
        if ($this->hasFixedPurity()) {
            $capabilities = $this->getCapabilities();

            if ($capabilities === Capabilities::NONE) {
                return 'pure-' . $this->value;
            }

            if ($capabilities === Capabilities::ALL) {
                return 'impure-' . $this->value;
            }
        }

        return $this->value . '<' . $this->purity->getId() . '>';
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

        return $this->getPurityString() . $param_string . $return_type_string;
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

        $prefix = $this->getPurityString();

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

        return $prefix . $param_string . $return_type_string;
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

        return $this->getPurityString() . $param_string . $return_type_string;
    }

    /**
     * @return array{list<FunctionLikeParameter>|null, Union|null, Union}|null
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

        // the purity of the passed closure binds the purity template, if any
        $purity = TemplateStandinTypeReplacer::replace(
            $this->purity,
            $template_result,
            $codebase,
            $statements_analyzer,
            $input_type instanceof TCallable || $input_type instanceof TClosure
                ? $input_type->purity
                : null,
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_lower_bound,
        );
        $replaced = $replaced || $this->purity !== $purity;

        if ($replaced) {
            return [$params, $return_type, $purity];
        }
        return null;
    }


    /**
     * @return array{list<FunctionLikeParameter>|null, Union|null, Union}|null
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

        $purity = TemplateInferredTypeReplacer::replace(
            $this->purity,
            $template_result,
            $codebase,
        );
        $replaced = $replaced || $purity !== $this->purity;

        if ($replaced) {
            return [$params, $return_type, $purity];
        }
        return null;
    }

    /**
     * @return list<string>
     * @psalm-pure
     */
    protected function getCallableChildNodeKeys(): array
    {
        return ['params', 'return_type', 'purity'];
    }
}
