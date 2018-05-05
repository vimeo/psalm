<?php
namespace Psalm\Type\Atomic;

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
}
