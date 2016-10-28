<?php

namespace Psalm\Type;

use Psalm\Type;

class ObjectLike extends Atomic
{
    public $value = 'array';

    /** @var array<string,Union> */
    public $properties;

    /**
     * Constructs a new instance of a generic type
     * @param string            $value
     * @param array<string,Union> $properties
     */
    public function __construct($value, array $properties)
    {
        $this->properties = $properties;
    }

    public function __toString()
    {
        return $this->value .
                '{' .
                implode(
                    ',',
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
}
