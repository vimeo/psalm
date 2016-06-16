<?php

namespace CodeInspector\Type;

use CodeInspector\Type;

class Union extends Type
{
    /** @var array<Type> */
    public $types;

    /**
     * Constructs an Union instance
     * @param array<AtomicType>     $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
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
}
