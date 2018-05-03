<?php
namespace Psalm\Type\Atomic;

class TInt extends Scalar
{
    /** @var array<string|int, bool>|null */
    public $values;

    /**
     * @param array<string|int, bool>|null $values
     */
    public function __construct(array $values = null)
    {
        $this->values = $values;
    }

    public function __toString()
    {
        return 'int';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'int';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->values ? 'int(' . implode(',', array_keys($this->values)) . ')' : 'int';
    }
}
