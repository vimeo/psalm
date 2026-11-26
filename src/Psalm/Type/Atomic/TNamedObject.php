<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\Capabilities;
use Psalm\Type;
use Psalm\Type\Atomic;

use function array_key_last;
use function implode;
use function strtolower;
use function substr;

/**
 * Denotes an object type where the type of the object is known e.g. `Exception`, `Throwable`, `Foo\Bar`
 *
 * @psalm-immutable
 * @api
 */
class TNamedObject extends Atomic
{
    use HasIntersectionTrait;

    public string $value;

    public bool $is_static_resolved = false;

    /**
     * @param string $value the name of the object
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     */
    public function __construct(
        string $value,
        public bool $is_static = false,
        /**
         * Whether or not this type can represent a child of the class named in $value
         */
        public bool $definite_class = false,
        array $extra_types = [],
        bool $from_docblock = false,
    ) {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }

        $this->value = $value;
        $this->extra_types = $extra_types;
        parent::__construct($from_docblock);
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

    #[Override]
    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        return $other_type::class === static::class
            && $this->value === $other_type->value
            && $this->is_static === $other_type->is_static
            && $this->intersectionTypesEqual($other_type->extra_types, $ensure_source_equality);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        if ($include_extra && $this->extra_types) {
            return $this->value . '&' . implode('&', $this->extra_types);
        }

        return $this->value;
    }

    #[Override]
    public function getId(bool $exact = true): string
    {
        if ($this->extra_types) {
            return $this->value . $this->getIntersectionTypesId($exact);
        }

        return $this->is_static && $exact ? $this->value . '&static' : $this->value;
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
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        if ($this->value === 'static') {
            return $analysis_php_version_id >= 8_00_00 ? 'static' : null;
        }

        if ($this->is_static && $this->value === $this_class) {
            return $analysis_php_version_id >= 8_00_00 ? 'static' : 'self';
        }

        return $this->addNativeIntersectionTypes(
            Type::getStringFromFQCLN($this->value, $namespace, $aliased_classes, $this_class, true),
            $namespace,
            $aliased_classes,
            $this_class,
            $analysis_php_version_id,
        );
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return (($this->value !== 'static' && $this->is_static === false) || $analysis_php_version_id >= 8_00_00)
            && $this->canExpressIntersectionTypesInPhp($analysis_php_version_id);
    }

    /**
     * Intersects $native, the native type of the class, with the classes of the intersection. A type of the
     * intersection that can't be part of a native intersection is left out, which leaves a wider type. Before
     * PHP 8.1, the last class of the intersection is declared alone.
     *
     * @param  array<lowercase-string, string> $aliased_classes
     */
    protected function addNativeIntersectionTypes(
        string $native,
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): string {
        if (!$this->extra_types) {
            return $native;
        }

        $natives = [strtolower($native) => $native];

        foreach ($this->extra_types as $extra_type) {
            if (!$extra_type instanceof TNamedObject) {
                continue;
            }

            $extra_native = $extra_type->toPhpString(
                $namespace,
                $aliased_classes,
                $this_class,
                $analysis_php_version_id,
            );

            if ($extra_native !== null && $extra_native !== 'static') {
                $natives[strtolower($extra_native)] ??= $extra_native;
            }
        }

        if ($analysis_php_version_id < 8_01_00) {
            return $natives[array_key_last($natives)];
        }

        return implode('&', $natives);
    }

    /**
     * Whether the native type expresses every type of the intersection: they're all classes from PHP 8.1
     */
    protected function canExpressIntersectionTypesInPhp(int $analysis_php_version_id): bool
    {
        if (!$this->extra_types) {
            return true;
        }

        if ($analysis_php_version_id < 8_01_00 || $this->value === 'static' || $this->is_static) {
            return false;
        }

        foreach ($this->extra_types as $extra_type) {
            if (!$extra_type instanceof TNamedObject
                || $extra_type->value === 'static'
                || $extra_type->is_static
                || !$extra_type->canBeFullyExpressedInPhp($analysis_php_version_id)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase,
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
    #[Override]
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
    /**
     * @psalm-pure
     */
    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['extra_types'];
    }

    /**
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties> $extra_types
     * @psalm-pure
     */
    public static function createFromName(
        string $value,
        bool $is_static = false,
        bool $definite_class = false,
        array $extra_types = [],
        bool $from_docblock = false,
    ): TNamedObject {
        if ($value === 'Closure') {
            return new TClosure(null, null, Capabilities::ALL, [], $extra_types, $from_docblock);
        }

        return new TNamedObject($value, $is_static, $definite_class, $extra_types, $from_docblock);
    }
}
