<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Interner;
use Psalm\StrId;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_keys;
use function array_map;
use function count;
use function implode;

/**
 * Denotes an object with specified member variables e.g. `object{foo:int, bar:string}`.
 *
 * @psalm-immutable
 * @api
 */
final class TObjectWithProperties extends TObject
{
    use HasIntersectionTrait;

    public bool $is_stringable_object_only = false;

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<string|int, Union> $properties
     * @param array<int, MethodIdentifier> $methods lowercase method name id => method id
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     */
    public function __construct(
        public array $properties,
        public array $methods = [],
        array $extra_types = [],
        bool $from_docblock = false,
    ) {
        $this->extra_types = $extra_types;

        $this->is_stringable_object_only = self::isStringableOnly($this->properties, $this->methods);

        parent::__construct($from_docblock);
    }

    /**
     * Creates the `stringable-object` type.
     *
     * @psalm-pure
     */
    public static function makeStringable(): self
    {
        return new self([], [StrId::__tostring => new MethodIdentifier(StrId::string, StrId::__tostring)]);
    }

    /**
     * @param array<string|int, Union> $properties
     * @param array<int, MethodIdentifier> $methods
     * @psalm-pure
     */
    private static function isStringableOnly(array $properties, array $methods): bool
    {
        return $properties === []
            && count($methods) === 1
            && isset($methods[StrId::__tostring])
            && $methods[StrId::__tostring]->fq_class_name === StrId::string;
    }

    /**
     * @param array<int, MethodIdentifier> $a
     * @param array<int, MethodIdentifier> $b
     * @psalm-pure
     */
    private static function methodsEqual(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        foreach ($a as $name => $method_id) {
            if (!isset($b[$name]) || !$method_id->equals($b[$name])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<string|int, Union> $properties
     */
    public function setProperties(array $properties): self
    {
        if ($properties === $this->properties) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->properties = $properties;

        $cloned->is_stringable_object_only = self::isStringableOnly($cloned->properties, $cloned->methods);

        return $cloned;
    }

    /**
     * @param array<int, MethodIdentifier> $methods lowercase method name id => method id
     */
    public function setMethods(array $methods): self
    {
        if (self::methodsEqual($methods, $this->methods)) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->methods = $methods;

        $cloned->is_stringable_object_only = self::isStringableOnly($cloned->properties, $cloned->methods);

        return $cloned;
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        $extra_types = '';

        if ($this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        $properties_string = implode(
            ', ',
            array_map(
                /**
                 * @psalm-pure
                 * @param  string|int $name
                 */
                static fn($name, Union $type): string => $name . ($type->possibly_undefined ? '?' : '') . ':'
                    . $type->getId($exact),
                array_keys($this->properties),
                $this->properties,
            ),
        );

        $methods_string = implode(
            ', ',
            array_map(
                /**
                 * @psalm-pure
                 */
                static fn(int $name): string => Interner::str($name) . '()',
                array_keys($this->methods),
            ),
        );

        return 'object{'
            . $properties_string . ($methods_string && $properties_string ? ', ' : '')
            . $methods_string
            . '}' . $extra_types;
    }

    /**
     * @param  array<int, int> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        bool $use_phpdoc_format,
    ): string {
        if ($use_phpdoc_format) {
            return 'object';
        }

        return 'object{' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @psalm-pure
                         * @param  string|int $name
                         */
                        static fn($name, Union $type): string =>
                            $name .
                            ($type->possibly_undefined ? '?' : '')
                            . ':'
                            . $type->toNamespacedString(
                                $namespace,
                                $aliased_classes,
                                $this_class,
                                false,
                            ),
                        array_keys($this->properties),
                        $this->properties,
                    ),
                ) .
                '}';
    }

    /**
     * @param  array<int, int> $aliased_classes
     */
    #[Override]
    public function toPhpString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        int $analysis_php_version_id,
    ): string {
        return $this->getKey();
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    #[Override]
    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (!$other_type instanceof self) {
            return false;
        }

        if (count($this->properties) !== count($other_type->properties)) {
            return false;
        }

        if (!self::methodsEqual($this->methods, $other_type->methods)) {
            return false;
        }

        foreach ($this->properties as $property_name => $property_type) {
            if (!isset($other_type->properties[$property_name])) {
                return false;
            }

            if (!$property_type->equals($other_type->properties[$property_name], $ensure_source_equality, false)) {
                return false;
            }
        }

        return true;
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
        ?int $calling_class = null,
        ?int $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0,
    ): self {
        $properties = [];

        foreach ($this->properties as $offset => $property) {
            $input_type_param = null;

            if ($input_type instanceof TObjectWithProperties
                && isset($input_type->properties[$offset])
            ) {
                $input_type_param = $input_type->properties[$offset];
            }

            $properties[$offset] = TemplateStandinTypeReplacer::replace(
                $property,
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
                $depth,
            );
        }

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
        if ($properties === $this->properties && !$intersection) {
            return $this;
        }
        return new static($properties, $this->methods, $intersection ?? $this->extra_types);
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase,
    ): self {
        $properties = $this->properties;
        foreach ($properties as $offset => $property) {
            $properties[$offset] = TemplateInferredTypeReplacer::replace(
                $property,
                $template_result,
                $codebase,
            );
        }
        $intersection = $this->replaceIntersectionTemplateTypesWithArgTypes(
            $template_result,
            $codebase,
        );
        if ($properties === $this->properties && !$intersection) {
            return $this;
        }
        return new static(
            $properties,
            $this->methods,
            $intersection ?? $this->extra_types,
        );
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['properties', 'extra_types'];
    }

    #[Override]
    public function getAssertionString(): string
    {
        return $this->getKey();
    }
}
