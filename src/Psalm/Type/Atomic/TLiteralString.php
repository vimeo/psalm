<?php
namespace Psalm\Type\Atomic;

class TLiteralString extends TString implements LiteralType
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
        return $this->values ? 'string(\'' . implode('\',\'', array_keys($this->values)) . '\')' : 'string';
    }

    /**
     * @return array<string|int, bool>
     */
    public function getValues()
    {
        return $this->values;
    }
}
