<?php
namespace Psalm\Type;

use Psalm\FunctionLikeParameter;

class Fn extends Atomic
{
    /**
     * @var string
     */
    public $value = 'Closure';

    /**
     * @var array<int, FunctionLikeParameter>
     */
    public $params = [];

    /**
     * @var Union
     */
    public $return_type;

    /**
     * Constructs a new instance of a generic type
     *
     * @param string                            $value
     * @param array<int, FunctionLikeParameter> $params
     * @param Union                             $return_type
     */
    public function __construct($value, array $params, Union $return_type)
    {
        $this->params = $params;
        $this->return_type = $return_type;
    }
}
