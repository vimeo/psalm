<?php
namespace Psalm\Type;

class Fn extends Atomic
{
    /**
     * @var string
     */
    public $value = 'Closure';

    /**
     * @var array<Union>
     */
    public $parameters = [];

    /**
     * @var Union
     */
    public $return_type;

    /**
     * Constructs a new instance of a generic type
     *
     * @param string            $value
     * @param array<int, Union> $parameters
     * @param Union             $return_type
     */
    public function __construct($value, array $parameters, Union $return_type)
    {
        $this->parameters = $parameters;
        $this->return_type = $return_type;
    }
}
