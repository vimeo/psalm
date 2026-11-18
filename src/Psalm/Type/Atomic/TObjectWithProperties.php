<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_keys;
use function array_map;
use function assert;
use function count;
use function implode;
use function is_string;
use function preg_match;

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
     * @param array<lowercase-string, string> $methods
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     * @param array<string|int, Union> $narrowed_from the declared type of each property that a condition narrowed
     * @param array<string, TTemplateParam> $template_keys the templates naming a property (`object{T: int}`), by
     *     `name:defining class`: the property is known once the template is bound
     * @param array<string, Union> $template_properties the types of those properties, by the same keys
     */
    public function __construct(
        public array $properties,
        public array $methods = [],
        array $extra_types = [],
        bool $from_docblock = false,
        public array $narrowed_from = [],
        public array $template_keys = [],
        public array $template_properties = [],
    ) {
        $this->extra_types = $extra_types;

        $this->is_stringable_object_only = $this->properties === []
            && $this->template_properties === []
            && $this->methods === ['__tostring' => 'string'];

        parent::__construct($from_docblock);
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

        $cloned->is_stringable_object_only = $cloned->properties === []
            && $cloned->template_properties === []
            && $cloned->methods === ['__tostring' => 'string'];

        return $cloned;
    }

    /**
     * @internal
     * @param array<string, Union> $template_properties
     */
    public function setTemplateProperties(array $template_properties): self
    {
        if ($template_properties === $this->template_properties) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->template_properties = $template_properties;

        return $cloned;
    }

    /**
     * @param array<string|int, Union> $narrowed_from
     */
    public function setNarrowedFrom(array $narrowed_from): self
    {
        if ($narrowed_from === $this->narrowed_from) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->narrowed_from = $narrowed_from;

        return $cloned;
    }

    /**
     * @param array<lowercase-string, string> $methods
     */
    public function setMethods(array $methods): self
    {
        if ($methods === $this->methods) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->methods = $methods;

        $cloned->is_stringable_object_only = $cloned->properties === []
            && $cloned->template_properties === []
            && $cloned->methods === ['__tostring' => 'string'];

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
                static fn(string $name): string => $name . '()',
                array_keys($this->methods),
            ),
        );

        foreach ($this->template_keys as $key => $template) {
            $type = $this->template_properties[$key];
            $properties_string .= ($properties_string === '' ? '' : ', ') . $template->getId($exact, true)
                . ($type->possibly_undefined ? '?' : '') . ':' . $type->getId($exact);
        }

        return 'object{'
            . $properties_string . ($methods_string && $properties_string ? ', ' : '')
            . $methods_string
            . '}' . $extra_types;
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
            return 'object';
        }

        $properties = [];

        foreach ($this->properties as $name => $type) {
            // quoted, a name stays a name where a template of that name is in scope
            $properties[] = (is_string($name) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) ? "'$name'" : $name)
                . ($type->possibly_undefined ? '?' : '') . ':'
                . $type->toNamespacedString($namespace, $aliased_classes, $this_class, false);
        }

        foreach ($this->template_keys as $key => $template) {
            $type = $this->template_properties[$key];
            $properties[] = $template->param_name . ($type->possibly_undefined ? '?' : '') . ':'
                . $type->toNamespacedString($namespace, $aliased_classes, $this_class, false);
        }

        return 'object{' . implode(', ', $properties) . '}';
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

        if (count($this->properties) !== count($other_type->properties)
            || count($this->template_properties) !== count($other_type->template_properties)
        ) {
            return false;
        }

        if ($this->methods !== $other_type->methods) {
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

        foreach ($this->template_properties as $key => $property_type) {
            if (!isset($other_type->template_properties[$key])
                || $this->template_keys[$key]->getId() !== $other_type->template_keys[$key]->getId()
                || !$property_type->equals($other_type->template_properties[$key], $ensure_source_equality, false)
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
        $properties = [];
        $template_properties = [];
        $named_count = count($this->properties);
        $entries = $this->template_properties ? $this->getStandinProperties() : $this->properties;

        foreach ($entries as $offset => $property) {
            $pending = $named_count-- <= 0;
            $input_properties = $input_type instanceof self
                ? ($pending ? $input_type->template_properties : $input_type->properties)
                : [];
            $input_type_param = $input_properties[$offset] ?? null;

            $property = TemplateStandinTypeReplacer::replace(
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
            if ($pending) {
                assert(is_string($offset));
                $template_properties[$offset] = $property;
            } else {
                $properties[$offset] = $property;
            }
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
        if ($properties === $this->properties
            && $template_properties === $this->template_properties
            && !$intersection
        ) {
            return $this;
        }
        return $this->setProperties($properties)
            ->setTemplateProperties($template_properties)
            ->setIntersectionTypes($intersection ?? $this->extra_types);
    }

    /**
     * @return iterable<array-key, Union>
     * @psalm-capabilities read-props
     */
    private function getStandinProperties(): iterable
    {
        yield from $this->properties;
        yield from $this->template_properties;
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
        return $this->setProperties($properties)->setIntersectionTypes($intersection ?? $this->extra_types);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['properties', 'extra_types', 'template_keys', 'template_properties'];
    }

    #[Override]
    public function getAssertionString(): string
    {
        return $this->getKey();
    }
}
