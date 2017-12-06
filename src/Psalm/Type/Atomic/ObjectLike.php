<?php
namespace Psalm\Type\Atomic;

use Psalm\Type;
use Psalm\Type\Union;

class ObjectLike extends \Psalm\Type\Atomic
{
    /**
     * @var array<string,Union>
     */
    public $properties;

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<string,Union> $properties
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
                         * @param  string $name
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
            return 'array';
        }

        return 'array{' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @param  string $name
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
    public function getGenericTypeParam()
    {
        $all_types = [];

        foreach ($this->properties as $property) {
            $all_types = array_merge($property->types, $all_types);
        }

        return Type::combineTypes(array_values($all_types));
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
