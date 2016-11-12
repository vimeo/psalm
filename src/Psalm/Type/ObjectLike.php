<?php
namespace Psalm\Type;

class ObjectLike extends Atomic
{
    /**
     * @var string
     */
    public $value = 'array';

    /**
     * @var array<string,Union>
     */
    public $properties;

    /**
     * Constructs a new instance of a generic type
     *
     * @param string            $value
     * @param array<string,Union> $properties
     */
    public function __construct($value, array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value .
                '{' .
                implode(
                    ', ',
                    array_map(
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
     * @return string
     */
    public function toNamespacedString(array $aliased_classes, $this_class)
    {
        return $this->value .
                '{' .
                implode(
                    ', ',
                    array_map(
                        function ($name, $type) use ($aliased_classes, $this_class) {
                            return $name . ':' . $type->toNamespacedString($aliased_classes, $this_class);
                        },
                        array_keys($this->properties),
                        $this->properties
                    )
                ) .
                '}';
    }
}
