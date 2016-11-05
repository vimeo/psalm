<?php
namespace Psalm\Type;

class Generic extends Atomic
{
    /**
     * @var array<Union>
     */
    public $type_params;

    /**
     * Constructs a new instance of a generic type
     *
     * @param string            $value
     * @param array<int,Union>  $type_params
     */
    public function __construct($value, array $type_params)
    {
        $this->value = $value;
        $this->type_params = $type_params;
    }

    /**
     * @return string
     */
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
