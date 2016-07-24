<?php

namespace CodeInspector\Type;

use CodeInspector\Type;

class Generic extends Atomic
{
    /** @var array<Type> */
    public $type_params;

    /** @var bool */
    public $is_empty;

    /**
     * Constructs a new instance of a generic type
     * @param string            $value
     * @param array<Type\Union> $type_params
     * @param bool              $is_empty
     */
    public function __construct($value, array $type_params, $is_empty = false)
    {
        $this->value = $value;
        $this->type_params = $type_params;
        $this->is_empty = $is_empty;
    }

    public function __toString()
    {
        return $this->value .
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
