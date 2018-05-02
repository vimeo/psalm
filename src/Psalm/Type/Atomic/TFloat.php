<?php
namespace Psalm\Type\Atomic;

class TFloat extends Scalar
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
        return 'float';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'float';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->values ? 'float(' . implode('|', array_keys($this->values)) . ')' : 'float';
    }
}
