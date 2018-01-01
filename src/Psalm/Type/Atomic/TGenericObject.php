<?php
namespace Psalm\Type\Atomic;

class TGenericObject extends TNamedObject implements Generic
{
    use GenericTrait;

    /**
     * @param string                            $value the name of the object
     * @param array<int, \Psalm\Type\Union>     $type_params
     */
    public function __construct($value, array $type_params)
    {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }
        $this->value = $value;
        $this->type_params = $type_params;
    }
}
