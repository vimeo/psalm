<?php
namespace Psalm\Type\Atomic;

use function array_keys;
use function array_map;
use function count;
use function get_class;
use function implode;
use function is_int;
use function sort;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Internal\Type\TypeCombination;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Represents an array where we know its key values
 */
class ObjectLike extends \Psalm\Type\Atomic
{
    /**
     * @var non-empty-array<string|int, Union>
     */
    public $properties;

    /**
     * @var array<string, bool>|null
     */
    public $class_strings = null;

    /**
     * @var bool - whether or not the objectlike has been created from an explicit array
     */
    public $sealed = false;

    /**
     * Whether or not the previous array had an unknown key type
     *
     * @var ?Union
     */
    public $previous_key_type = null;

    /**
     * Whether or not to allow new properties to be asserted on the given array
     *
     * @var ?Union
     */
    public $previous_value_type = null;

    /**
     * @var bool - if this is a list of sequential elements
     */
    public $is_list = false;

    const KEY = 'array';

    /**
     * Constructs a new instance of a generic type
     *
     * @param non-empty-array<string|int, Union> $properties
     * @param array<string, bool> $class_strings
     */
    public function __construct(array $properties, array $class_strings = null)
    {
        $this->properties = $properties;
        $this->class_strings = $class_strings;
    }

    public function __toString()
    {
        $union_type_parts = array_map(
            /**
             * @param  string|int $name
             * @param  Union $type
             *
             * @return string
             */
            function ($name, Union $type) {
                return $name . ($type->possibly_undefined ? '?' : '') . ': ' . $type;
            },
            array_keys($this->properties),
            $this->properties
        );
        sort($union_type_parts);
        /** @psalm-suppress MixedOperand */
        return static::KEY . '{' . implode(', ', $union_type_parts) . '}';
    }

    public function getId(bool $nested = false)
    {
        $union_type_parts = array_map(
            /**
             * @param  string|int $name
             * @param  Union $type
             *
             * @return string
             */
            function ($name, Union $type) {
                return $name . ($type->possibly_undefined ? '?' : '') . ': ' . $type->getId();
            },
            array_keys($this->properties),
            $this->properties
        );
        sort($union_type_parts);
        /** @psalm-suppress MixedOperand */
        return static::KEY . '{' .
                implode(', ', $union_type_parts) .
                '}'
                . ($this->previous_value_type
                    ? '<' . ($this->previous_key_type ? $this->previous_key_type->getId() . ', ' : '')
                        . $this->previous_value_type->getId() . '>'
                    : '');
    }

    /**
     * @param  array<string, string> $aliased_classes
     *
     * @return string
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ) {
        if ($use_phpdoc_format) {
            return $this->getGenericArrayType()->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                $use_phpdoc_format
            );
        }

        /** @psalm-suppress MixedOperand */
        return static::KEY . '{' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @param  string|int $name
                         * @param  Union  $type
                         *
                         * @return string
                         */
                        function (
                            $name,
                            Union $type
                        ) use (
                            $namespace,
                            $aliased_classes,
                            $this_class,
                            $use_phpdoc_format
                        ) {
                            return $name . ($type->possibly_undefined ? '?' : '') . ': ' . $type->toNamespacedString(
                                $namespace,
                                $aliased_classes,
                                $this_class,
                                $use_phpdoc_format
                            );
                        },
                        array_keys($this->properties),
                        $this->properties
                    )
                ) .
                '}';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return string
     */
    public function toPhpString($namespace, array $aliased_classes, $this_class, $php_major_version, $php_minor_version)
    {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }

    /**
     * @return Union
     */
    public function getGenericKeyType()
    {
        $key_types = [];

        foreach ($this->properties as $key => $_) {
            if (is_int($key)) {
                $key_types[] = new Type\Atomic\TLiteralInt($key);
            } elseif (isset($this->class_strings[$key])) {
                $key_types[] = new Type\Atomic\TLiteralClassString($key);
            } else {
                $key_types[] = new Type\Atomic\TLiteralString($key);
            }
        }

        $key_type = TypeCombination::combineTypes($key_types);

        if ($this->previous_key_type) {
            $key_type = Type::combineUnionTypes($this->previous_key_type, $key_type);
        }

        return $key_type;
    }

    /**
     * @return Union
     */
    public function getGenericValueType()
    {
        $value_type = null;

        foreach ($this->properties as $property) {
            if ($value_type === null) {
                $value_type = clone $property;
            } else {
                $value_type = Type::combineUnionTypes($property, $value_type);
            }
        }

        if ($this->previous_value_type) {
            $value_type = Type::combineUnionTypes($this->previous_value_type, $value_type);
        }

        $value_type->possibly_undefined = false;

        return $value_type;
    }

    /**
     * @return Type\Atomic\TArray
     */
    public function getGenericArrayType()
    {
        $key_types = [];
        $value_type = null;

        foreach ($this->properties as $key => $property) {
            if (is_int($key)) {
                $key_types[] = new Type\Atomic\TLiteralInt($key);
            } elseif (isset($this->class_strings[$key])) {
                $key_types[] = new Type\Atomic\TLiteralClassString($key);
            } else {
                $key_types[] = new Type\Atomic\TLiteralString($key);
            }

            if ($value_type === null) {
                $value_type = clone $property;
            } else {
                $value_type = Type::combineUnionTypes($property, $value_type);
            }
        }

        $key_type = TypeCombination::combineTypes($key_types);

        if ($this->previous_value_type) {
            $value_type = Type::combineUnionTypes($this->previous_value_type, $value_type);
        }

        if ($this->previous_key_type) {
            $key_type = Type::combineUnionTypes($this->previous_key_type, $key_type);
        }

        $value_type->possibly_undefined = false;

        if ($this->sealed || $this->previous_value_type) {
            $array_type = new TNonEmptyArray([$key_type, $value_type]);
            $array_type->count = count($this->properties);
        } else {
            $array_type = new TArray([$key_type, $value_type]);
        }

        return $array_type;
    }

    public function __clone()
    {
        foreach ($this->properties as &$property) {
            $property = clone $property;
        }
    }

    /**
     * @return string
     */
    public function getKey()
    {
        /** @var string */
        return static::KEY;
    }

    public function setFromDocblock()
    {
        $this->from_docblock = true;

        foreach ($this->properties as $property_type) {
            $property_type->setFromDocblock();
        }
    }

    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase = null,
        Atomic $input_type = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) : Atomic {
        $object_like = clone $this;

        foreach ($this->properties as $offset => $property) {
            $input_type_param = null;

            if ($input_type instanceof Atomic\ObjectLike
                && isset($input_type->properties[$offset])
            ) {
                $input_type_param = $input_type->properties[$offset];
            }

            $object_like->properties[$offset] = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $property,
                $template_result,
                $codebase,
                $input_type_param,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth
            );
        }

        return $object_like;
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>     $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types, ?Codebase $codebase)
    {
        foreach ($this->properties as $property) {
            $property->replaceTemplateTypesWithArgTypes(
                $template_types,
                $codebase
            );
        }
    }

    /**
     * @return list<Type\Atomic\TTemplateParam>
     */
    public function getTemplateTypes() : array
    {
        $template_types = [];

        foreach ($this->properties as $property) {
            $template_types = \array_merge($template_types, $property->getTemplateTypes());
        }

        return $template_types;
    }

    /**
     * @return bool
     */
    public function equals(Atomic $other_type)
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if (count($this->properties) !== count($other_type->properties)) {
            return false;
        }

        if ($this->sealed !== $other_type->sealed) {
            return false;
        }

        foreach ($this->properties as $property_name => $property_type) {
            if (!isset($other_type->properties[$property_name])) {
                return false;
            }

            if (!$property_type->equals($other_type->properties[$property_name])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return $this->getKey();
    }

    /**
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     * @param  bool             $inferred
     *
     * @return void
     */
    public function check(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $prevent_template_covariance = false
    ) {
        if ($this->checked) {
            return;
        }

        foreach ($this->properties as $property_type) {
            $property_type->check(
                $source,
                $code_location,
                $suppressed_issues,
                $phantom_classes,
                $inferred,
                $prevent_template_covariance
            );
        }

        $this->checked = true;
    }

    public function getList() : TList
    {
        if (!$this->is_list) {
            throw new \UnexpectedValueException('Object-like array must be a list for conversion');
        }

        return new TNonEmptyList($this->getGenericValueType());
    }
}
