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
     * @param  array<string> $aliased_classes
     * @param  string        $this_class
     * @param  bool          $use_phpdoc_format
     * @return string
     */
    public function toNamespacedString(array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        if ($use_phpdoc_format) {
            return $this->value;
        }

        return $this->value .
                '{' .
                implode(
                    ', ',
                    array_map(
                        function ($name, Union $type) use ($aliased_classes, $this_class, $use_phpdoc_format) {
                            return $name . ':' . $type->toNamespacedString($aliased_classes, $this_class, $use_phpdoc_format);
                        },
                        array_keys($this->properties),
                        $this->properties
                    )
                ) .
                '}';
    }
}
