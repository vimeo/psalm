<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Union;
use UnexpectedValueException;

use function addslashes;
use function count;
use function get_class;
use function implode;
use function is_int;
use function is_string;
use function preg_match;
use function sort;
use function str_replace;

/**
 * Represents an 'object-like array' - an array with known keys.
 * @psalm-immutable
 */
class TKeyedArray extends Atomic
{
    /**
     * @var non-empty-array<string|int, Union>
     */
    public $properties;

    /**
     * @var array<string, bool>|null
     */
    public $class_strings;

    /**
     * If the shape has fallback params then they are here
     *
     * @var ?list{Union, Union}
     */
    public $fallback_params;

    /**
     * @var bool - if this is a list of sequential elements
     */
    public $is_list = false;

    /** @var non-empty-lowercase-string */
    protected const NAME_ARRAY = 'array';
    /** @var non-empty-lowercase-string */
    protected const NAME_LIST = 'list';

    /**
     * Constructs a new instance of a generic type
     *
     * @param non-empty-array<string|int, Union> $properties
     * @param ?list{Union, Union} $fallback_params
     * @param array<string, bool> $class_strings
     */
    public function __construct(
        array $properties,
        ?array $class_strings = null,
        ?array $fallback_params = null,
        bool $is_list = false,
        bool $from_docblock = false
    ) {
        $this->properties = $properties;
        $this->class_strings = $class_strings;
        $this->fallback_params = $fallback_params;
        $this->is_list = $is_list;
        $this->from_docblock = $from_docblock;
    }

    /**
     * @param non-empty-array<string|int, Union> $properties
     *
     * @return static
     */
    public function setProperties(array $properties): self
    {
        if ($properties === $this->properties) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->properties = $properties;
        return $cloned;
    }

    /**
     * @return static
     */
    public function makeSealed(): self
    {
        if ($this->fallback_params === null) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->fallback_params = null;
        return $cloned;
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        $property_strings = [];

        if ($this->is_list) {
            $use_list_syntax = true;
            foreach ($this->properties as $property) {
                if ($property->possibly_undefined) {
                    $use_list_syntax = false;
                    break;
                }
            }
        } else {
            $use_list_syntax = false;
        }

        foreach ($this->properties as $name => $type) {
            if ($use_list_syntax) {
                $property_strings[$name] = $type->getId($exact);
                continue;
            }

            $class_string_suffix = '';
            if (isset($this->class_strings[$name])) {
                $class_string_suffix = '::class';
            }

            $name = $this->escapeAndQuote($name);

            $property_strings[$name] = $name . $class_string_suffix . ($type->possibly_undefined ? '?' : '')
                . ': ' . $type->getId($exact);
        }

        if ($this->is_list) {
            $key = static::NAME_LIST;
        } else {
            $key = static::NAME_ARRAY;
            sort($property_strings);
        }

        $params_part = $this->fallback_params !== null
            ? ', ...<' . $this->fallback_params[0]->getId($exact) . ', '
                . $this->fallback_params[1]->getId($exact) . '>'
            : '';

        return $key . '{' . implode(', ', $property_strings) . $params_part . '}';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($use_phpdoc_format) {
            return $this->getGenericArrayType()->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                true
            );
        }

        $suffixed_properties = [];

        if ($this->is_list) {
            $use_list_syntax = true;
            foreach ($this->properties as $property) {
                if ($property->possibly_undefined) {
                    $use_list_syntax = false;
                    break;
                }
            }
        } else {
            $use_list_syntax = false;
        }

        foreach ($this->properties as $name => $type) {
            if ($use_list_syntax) {
                $suffixed_properties[$name] = $type->toNamespacedString(
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    false
                );
                continue;
            }

            $class_string_suffix = '';
            if (isset($this->class_strings[$name])) {
                $class_string_suffix = '::class';
            }

            $name = $this->escapeAndQuote($name);

            $suffixed_properties[$name] = $name . $class_string_suffix . ($type->possibly_undefined ? '?' : '') . ': ' .
                $type->toNamespacedString(
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    false
                );
        }

        $params_part = $this->fallback_params !== null ? ',...' : '';

        return  ($this->is_list ? static::NAME_LIST : static::NAME_ARRAY)
                . '{' . implode(', ', $suffixed_properties) . $params_part . '}';
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

    public function getGenericKeyType(bool $possibly_undefined = false): Union
    {
        $key_types = [];

        foreach ($this->properties as $key => $_) {
            if (is_int($key)) {
                $key_types[] = new TLiteralInt($key);
            } elseif (isset($this->class_strings[$key])) {
                $key_types[] = new TLiteralClassString($key);
            } else {
                $key_types[] = new TLiteralString($key);
            }
        }

        $key_type = TypeCombiner::combine($key_types);

        /** @psalm-suppress InaccessibleProperty We just created this type */
        $key_type->possibly_undefined = $possibly_undefined;

        if ($this->fallback_params === null) {
            return $key_type;
        }

        return Type::combineUnionTypes($this->fallback_params[0], $key_type);
    }

    public function getGenericValueType(bool $possibly_undefined = false): Union
    {
        $value_type = null;

        foreach ($this->properties as $property) {
            $value_type = Type::combineUnionTypes($property, $value_type);
        }

        return Type::combineUnionTypes(
            $this->fallback_params[1] ?? null,
            $value_type,
            null,
            false,
            true,
            500,
            $possibly_undefined
        );
    }

    /**
     * @return TArray|TNonEmptyArray
     */
    public function getGenericArrayType(bool $allow_non_empty = true): TArray
    {
        $key_types = [];
        $value_type = null;

        $has_defined_keys = false;

        foreach ($this->properties as $key => $property) {
            if (is_int($key)) {
                $key_types[] = new TLiteralInt($key);
            } elseif (isset($this->class_strings[$key])) {
                $key_types[] = new TLiteralClassString($key);
            } else {
                $key_types[] = new TLiteralString($key);
            }

            $value_type = Type::combineUnionTypes($property, $value_type);

            if (!$property->possibly_undefined) {
                $has_defined_keys = true;
            }
        }

        $key_type = TypeCombiner::combine($key_types);

        if ($this->fallback_params !== null) {
            $key_type = Type::combineUnionTypes($this->fallback_params[0], $key_type);
            $value_type = Type::combineUnionTypes($this->fallback_params[1], $value_type);
        }

        $value_type = $value_type->setPossiblyUndefined(false);

        if ($allow_non_empty && ($this->fallback_params !== null || $has_defined_keys)) {
            $array_type = new TNonEmptyArray([$key_type, $value_type]);
        } else {
            $array_type = new TArray([$key_type, $value_type]);
        }

        return $array_type;
    }

    public function isNonEmpty(): bool
    {
        foreach ($this->properties as $property) {
            if (!$property->possibly_undefined) {
                return true;
            }
        }

        return false;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
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
        $properties = $this->properties;

        foreach ($properties as $offset => $property) {
            $input_type_param = null;

            if ($input_type instanceof TKeyedArray
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
                $depth
            );
        }

        if ($properties === $this->properties) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->properties = $properties;
        return $cloned;
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): self {
        $properties = $this->properties;
        foreach ($properties as $offset => $property) {
            $properties[$offset] = TemplateInferredTypeReplacer::replace(
                $property,
                $template_result,
                $codebase
            );
        }
        if ($properties !== $this->properties) {
            $cloned = clone $this;
            $cloned->properties = $properties;
            return $cloned;
        }
        return $this;
    }

    protected function getChildNodeKeys(): array
    {
        return ['properties'];
    }

    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if (count($this->properties) !== count($other_type->properties)) {
            return false;
        }

        if (($this->fallback_params === null) !== ($other_type->fallback_params === null)) {
            return false;
        }

        if ($this->fallback_params !== null && $other_type->fallback_params !== null) {
            if (!$this->fallback_params[0]->equals($other_type->fallback_params[0])) {
                return false;
            }

            if (!$this->fallback_params[1]->equals($other_type->fallback_params[1])) {
                return false;
            }
        }

        foreach ($this->properties as $property_name => $property_type) {
            if (!isset($other_type->properties[$property_name])) {
                return false;
            }

            if (!$property_type->equals($other_type->properties[$property_name], $ensure_source_equality)) {
                return false;
            }
        }

        return true;
    }

    public function getAssertionString(): string
    {
        return $this->is_list ? 'list' : 'array';
    }

    public function getList(): TList
    {
        if (!$this->is_list) {
            throw new UnexpectedValueException('Object-like array must be a list for conversion');
        }

        return $this->isNonEmpty()
            ? new TNonEmptyList($this->getGenericValueType())
            : new TList($this->getGenericValueType());
    }

    /**
     * @param string|int $name
     * @return string|int
     */
    private function escapeAndQuote($name)
    {
        if (is_string($name) && ($name === '' || preg_match('/[^a-zA-Z0-9_]/', $name))) {
            $name = '\'' . str_replace("\n", '\n', addslashes($name)) . '\'';
        }

        return $name;
    }
}
