<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TLiteralFloat extends TFloat implements LiteralType
{
    /** @var array<string, bool> */
    public $values;

    /**
     * @param array<string, bool> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->values ? 'float(' . implode(',', array_keys($this->values)) . ')' : 'float';
    }

    /**
     * @return array<string, bool>
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return bool
     */
    public function equals(Atomic $other_type)
    {
        if (!$other_type instanceof self) {
            return false;
        }

        return $this->values == $other_type->values;
    }
}
