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
use Psalm\Internal\Analyzer\StatementsAnalyzer;
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
        $property_strings = array_map(
            function ($name, Union $type) {
                if ($this->is_list && $this->sealed) {
                    return (string) $type;
                }

                if (\is_string($name) && \preg_match('/[ "\'\\\\.\n:]/', $name)) {
                    $name = '\'' . \str_replace("\n", '\n', \addslashes($name)) . '\'';
                }

                return $name . ($type->possibly_undefined ? '?' : '') . ': ' . $type;
            },
            array_keys($this->properties),
            $this->properties
        );

        if (!$this->is_list) {
            sort($property_strings);
        }

        /** @psalm-suppress MixedOperand */
        return static::KEY . '{' . implode(', ', $property_strings) . '}';
    }

    public function getId(bool $nested = false)
    {
        $property_strings = array_map(
            function ($name, Union $type) {
                if ($this->is_list && $this->sealed) {
                    return $type->getId();
                }

                if (\is_string($name) && \preg_match('/[ "\'\\\\.\n:]/', $name)) {
                    $name = '\'' . \str_replace("\n", '\n', \addslashes($name)) . '\'';
                }

                return $name . ($type->possibly_undefined ? '?' : '') . ': ' . $type->getId();
            },
            array_keys($this->properties),
            $this->properties
        );

        if (!$this->is_list) {
            sort($property_strings);
        }

        /** @psalm-suppress MixedOperand */
        return static::KEY . '{' .
                implode(', ', $property_strings) .
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
                        function (
                            $name,
                            Union $type
                        ) use (
                            $namespace,
                            $aliased_classes,
                            $this_class,
                            $use_phpdoc_format
                        ) {
                            if (\is_string($name) && \preg_match('/[ "\'\\\\.\n:]/', $name)) {
                                $name = '\'' . \str_replace("\n", '\n', \addslashes($name)) . '\'';
                            }

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

        $key_type->possibly_undefined = false;

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

        $has_defined_keys = false;

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

            if (!$value_type->possibly_undefined) {
                $has_defined_keys = true;
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

        if ($this->previous_value_type || $has_defined_keys) {
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
    public function getKey(bool $include_extra = true)
    {
        /** @var string */
        return static::KEY;
    }

    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        ?Codebase $codebase = null,
        ?StatementsAnalyzer $statements_analyzer = null,
        Atomic $input_type = null,
        ?int $input_arg_offset = null,
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
                $statements_analyzer,
                $input_type_param,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth
            );
        }

        return $object_like;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        foreach ($this->properties as $property) {
            $property->replaceTemplateTypesWithArgTypes(
                $template_result,
                $codebase
            );
        }
    }

    public function getChildNodes() : array
    {
        return $this->properties;
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

    public function getList() : TList
    {
        if (!$this->is_list) {
            throw new \UnexpectedValueException('Object-like array must be a list for conversion');
        }

        return new TNonEmptyList($this->getGenericValueType());
    }
}
