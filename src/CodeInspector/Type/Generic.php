<?php

namespace CodeInspector\Type;

use CodeInspector\Type;

class Generic extends Atomic
{
    /** @var array<Type> */
    public $value;

    /**
     * Constructs a new instance of a generic type
     * @param string        $value
     * @param array<Type>   $type_params
     * @param boolean       $negated
     */
    public function __construct($value, array $type_params, $negated = false)
    {
        $this->value = $value;
        $this->negated = $negated;
        $this->type_params = $type_params;
    }

    public function __toString()
    {
        return ($this->negated ? '!' : '') .
                $this->value .
                '<' .
                implode(
                    ',',
                    array_map(
                        function ($type_param) {
                            return (string) $type_param;
                        },
                        $this->type_params
                    )
                ) .
                '>';
    }
}
