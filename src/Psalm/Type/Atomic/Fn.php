<?php
namespace Psalm\Type\Atomic;

use Psalm\FunctionLikeParameter;
use Psalm\Type\Union;

/**
 * Represents a closure where we know the return type and params
 */
class Fn extends TNamedObject
{
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
        $this->value = 'Closure';
        $this->params = $params;
        $this->return_type = $return_type;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'Closure';
    }
}
