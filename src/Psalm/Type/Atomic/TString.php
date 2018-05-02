<?php
namespace Psalm\Type\Atomic;

class TString extends Scalar
{
    /** @var array<string, bool>|null */
    public $values;

    /**
     * @param array<string, bool>|null $values
     */
    public function __construct(array $values = null)
    {
        $this->values = $values;
    }

    public function __toString()
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->values ? 'string(\'' . implode('\',\'', array_keys($this->values)) . '\')' : 'string';
    }
}
