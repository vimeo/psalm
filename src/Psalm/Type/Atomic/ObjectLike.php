<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Internal\Type\TypeCombination;
use Psalm\Type\Union;

/**
 * Represents an array where we know its key values
 */
class ObjectLike extends \Psalm\Type\Atomic
{
    /**
     * @var array<string|int, Union>
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
     * Whether or not to allow new properties to be asserted on the given array
     *
     * @var bool
     */
    public $had_mixed_value = false;

    const KEY = 'array';

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<string|int, Union> $properties
     * @param array<string, bool> $class_strings
     */
    public function __construct(array $properties, array $class_strings = null)
    {
        $this->properties = $properties;
        $this->class_strings = $class_strings;
    }

    public function __toString()
    {
        /** @psalm-suppress MixedOperand */
        return static::KEY . '{' .
                implode(
                    ', ',
                    array_map(
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
                    )
                ) .
                '}';
    }

    public function getId()
    {
        /** @psalm-suppress MixedOperand */
        return static::KEY . '{' .
                implode(
                    ', ',
                    array_map(
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
                    )
                ) .
                '}';
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
        if ($this->had_mixed_value) {
            return Type::getArrayKey();
        }

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

        return TypeCombination::combineTypes($key_types);
    }

    /**
     * @return Union
     */
    public function getGenericValueType()
    {
        if ($this->had_mixed_value) {
            return Type::getMixed();
        }

        $value_type = null;

        foreach ($this->properties as $property) {
            if ($value_type === null) {
                $value_type = clone $property;
            } else {
                $value_type = Type::combineUnionTypes($property, $value_type);
            }
        }

        if (!$value_type) {
            throw new \UnexpectedValueException('$value_type should not be null here');
        }

        $value_type->possibly_undefined = false;

        return $value_type;
    }

    /**
     * @return Type\Atomic\TArray
     */
    public function getGenericArrayType()
    {
        if ($this->had_mixed_value) {
            return new TNonEmptyArray([Type::getArrayKey(), Type::getMixed()]);
        }

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

        if (!$value_type) {
            throw new \UnexpectedValueException('$value_type should not be null here');
        }

        $value_type->possibly_undefined = false;

        if ($this->sealed) {
            $array_type = new TNonEmptyArray([TypeCombination::combineTypes($key_types), $value_type]);
            $array_type->count = count($this->properties);
        } else {
            $array_type = new TArray([TypeCombination::combineTypes($key_types), $value_type]);
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
        return 'array';
    }

    public function setFromDocblock()
    {
        $this->from_docblock = true;

        foreach ($this->properties as $property_type) {
            $property_type->setFromDocblock();
        }
    }

    /**
     * @param  array<string, array<string, array{Type\Union}>>    $template_types
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>     $generic_params
     * @param  Atomic|null              $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array &$template_types,
        array &$generic_params,
        Codebase $codebase = null,
        Atomic $input_type = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) {
        foreach ($this->properties as $offset => $property) {
            $input_type_param = null;

            if ($input_type instanceof Atomic\ObjectLike
                && isset($input_type->properties[$offset])
            ) {
                $input_type_param = $input_type->properties[$offset];
            }

            $property->replaceTemplateTypesWithStandins(
                $template_types,
                $generic_params,
                $codebase,
                $input_type_param,
                $replace,
                $add_upper_bound,
                $depth
            );
        }
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>  $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types, ?Codebase $codebase)
    {
        foreach ($this->properties as $property) {
            $property->replaceTemplateTypesWithArgTypes($template_types);
        }
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
}
