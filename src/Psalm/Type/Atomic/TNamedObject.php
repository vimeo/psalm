<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type;
use Psalm\Type\Atomic;

use function array_map;
use function implode;
use function strrpos;
use function substr;

/**
 * Denotes an object type where the type of the object is known e.g. `Exception`, `Throwable`, `Foo\Bar`
 *
 * @psalm-immutable
 */
class TNamedObject extends Atomic
{
    use HasIntersectionTrait;

    /**
     * @var string
     */
    public $value;

    /**
     * @var bool
     */
    public $is_static = false;

    /**
     * @var bool
     */
    public $is_static_resolved = false;

    /**
     * Whether or not this type can represent a child of the class named in $value
     *
     * @var bool
     */
    public $definite_class = false;

    /**
     * @param string $value the name of the object
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties> $extra_types
     */
    public function __construct(
        string $value,
        bool $is_static = false,
        bool $definite_class = false,
        array $extra_types = [],
        bool $from_docblock = false
    ) {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }

        $this->value = $value;
        $this->is_static = $is_static;
        $this->definite_class = $definite_class;
        $this->extra_types = $extra_types;
        $this->from_docblock = $from_docblock;
    }

    /**
     * @return static
     */
    public function setIsStatic(bool $is_static, ?bool $is_static_resolved = null): self
    {
        $is_static_resolved ??= $this->is_static_resolved;
        if ($this->is_static === $is_static && $this->is_static_resolved === $is_static_resolved) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->is_static = $is_static;
        $cloned->is_static_resolved = $is_static_resolved;
        return $cloned;
    }

    /**
     * @return static
     */
    public function setValue(string $value): self
    {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }
        if ($value === $this->value) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->value = $value;
        return $cloned;
    }
    /**
     * @return static
     */
    public function setValueIsStatic(string $value, bool $is_static, ?bool $is_static_resolved = null): self
    {
        $is_static_resolved ??= $this->is_static_resolved;
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }
        if ($value === $this->value
            && $this->is_static === $is_static
            && $this->is_static_resolved === $is_static_resolved
        ) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->value = $value;
        $cloned->is_static = $is_static;
        $cloned->is_static_resolved = $is_static;
        return $cloned;
    }
    public function getKey(bool $include_extra = true): string
    {
        if ($include_extra && $this->extra_types) {
            return $this->value . '&' . implode('&', $this->extra_types);
        }

        return $this->value;
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        if ($this->extra_types) {
            return $this->value . '&' . implode(
                '&',
                array_map(
                    static fn(Atomic $type): string => $type->getId($exact, true),
                    $this->extra_types,
                ),
            );
        }

        return $this->is_static && $exact ? $this->value . '&static' : $this->value;
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
        if ($this->value === 'static') {
            return 'static';
        }

        $intersection_types = $this->getNamespacedIntersectionTypes(
            $namespace,
            $aliased_classes,
            $this_class,
            $use_phpdoc_format,
        );

        return Type::getStringFromFQCLN(
            $this->value,
            $namespace,
            $aliased_classes,
            $this_class,
            true,
            $this->is_static,
        ) . $intersection_types;
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
        if ($this->value === 'static') {
            return $analysis_php_version_id >= 8_00_00 ? 'static' : null;
        }

        if ($this->is_static && $this->value === $this_class) {
            return $analysis_php_version_id >= 8_00_00 ? 'static' : 'self';
        }

        $result = $this->toNamespacedString($namespace, $aliased_classes, $this_class, false);
        $intersection = strrpos($result, '&');
        if ($intersection === false || $analysis_php_version_id >= 8_01_00) {
            return $result;
        }
        return substr($result, $intersection+1);
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return ($this->value !== 'static' && $this->is_static === false) || $analysis_php_version_id >= 8_00_00;
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): self {
        $intersection = $this->replaceIntersectionTemplateTypesWithArgTypes($template_result, $codebase);
        if (!$intersection) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->extra_types = $intersection;
        return $cloned;
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
        if ($intersection) {
            $cloned = clone $this;
            $cloned->extra_types = $intersection;
            return $cloned;
        }
        return $this;
    }
    protected function getChildNodeKeys(): array
    {
        return ['extra_types'];
    }
}
