<?php

namespace CodeInspector\Type;

use CodeInspector\Type;

class Union extends Type
{
    /** @var array<Type> */
    public $types = [];

    /**
     * Constructs an Union instance
     * @param array<AtomicType>     $types
     */
    public function __construct(array $types)
    {
        foreach ($types as $type) {
            $this->types[$type->value] = $type;
        }
    }

    public function __clone()
    {
        foreach ($this->types as &$type) {
            $type = clone $type;
        }
    }

    public function __toString()
    {
        return implode(
            '|',
            array_map(
                function ($type) {
                    return (string) $type;
                },
                $this->types
            )
        );
    }

    public function removeType($type_string) {
        unset($this->types[$type_string]);
    }

    public function hasType($type_string) {
        return isset($this->types[$type_string]);
    }

    public function removeObjects() {
        foreach ($this->types as $key => $type) {
            if ($key[0] === strtoupper($key[0])) {
                unset($this->types[$key]);
            }
        }
    }
}
