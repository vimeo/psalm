<?php
namespace Psalm\Type\Atomic;

use Psalm\Type;
use Psalm\Type\Union;

class ObjectLike extends \Psalm\Type\Atomic
{
    /**
     * @var array<string|int, Union>
     */
    public $properties;

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<string|int, Union> $properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public function __toString()
    {
        return 'array{' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @param  string|int $name
                         * @param  string $type
                         *
                         * @return string
                         */
                        function ($name, $type) {
                            return $name . ':' . $type;
                        },
                        array_keys($this->properties),
                        $this->properties
                    )
                ) .
                '}';
    }

    /**
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    public function toNamespacedString(array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        if ($use_phpdoc_format) {
            return $this->getGenericArrayType()->toNamespacedString($aliased_classes, $this_class, $use_phpdoc_format);
        }

        return 'array{' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @param  string|int $name
                         * @param  Union  $type
                         *
                         * @return string
                         */
                        function ($name, Union $type) use ($aliased_classes, $this_class, $use_phpdoc_format) {
                            return $name . ':' . $type->toNamespacedString(
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
     * @return Union
     */
    public function getGenericKeyType()
    {
        $key_types = [];

        foreach ($this->properties as $key => $_) {
            if (is_int($key) || preg_match('/^\d+$/', $key)) {
                $key_types[] = new Type\Atomic\TInt();
            } else {
                $key_types[] = new Type\Atomic\TString();
            }
        }

        return Type::combineTypes($key_types);
    }

    /**
     * @return Union
     */
    public function getGenericValueType()
    {
        $value_types = [];

        foreach ($this->properties as $property) {
            $value_types = array_merge($property->types, $value_types);
        }

        return Type::combineTypes(array_values($value_types));
    }

    /**
     * @return Type\Atomic\TArray
     */
    public function getGenericArrayType()
    {
        $key_types = [];
        $value_types = [];

        foreach ($this->properties as $key => $property) {
            if (is_int($key) || preg_match('/^\d+$/', $key)) {
                $key_types[] = new Type\Atomic\TInt();
            } else {
                $key_types[] = new Type\Atomic\TString();
            }

            $value_types = array_merge($property->types, $value_types);
        }

        return new TArray([Type::combineTypes($key_types), Type::combineTypes(array_values($value_types))]);
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
        foreach ($this->properties as $property_type) {
            $property_type->setFromDocblock();
        }
    }
}
