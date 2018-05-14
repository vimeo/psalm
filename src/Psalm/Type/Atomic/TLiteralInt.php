<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TLiteralInt extends TInt implements LiteralType
{
    /** @var array<string|int, bool> */
    public $values;

    /**
     * @param array<string|int, bool> $values
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
        return $this->values ? 'int(' . implode(',', array_keys($this->values)) . ')' : 'int';
    }

    /**
     * @return array<string|int, bool>
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
