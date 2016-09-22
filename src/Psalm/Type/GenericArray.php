<?php

namespace Psalm\Type;

use Psalm\Type;

class GenericArray extends Generic
{
    public $value = 'array';

    /**
     * Constructs a new instance of a generic type
     * @param string            $value
     * @param array<Type\Union> $type_params
     */
    public function __construct($value, array $type_params)
    {
        $this->type_params = $type_params;
    }
}
