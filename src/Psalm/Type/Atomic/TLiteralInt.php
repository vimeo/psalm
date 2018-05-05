<?php
namespace Psalm\Type\Atomic;

class TLiteralInt extends TInt implements LiteralType
{
    /** @var array<int, bool> */
    public $values;

    /**
     * @param array<int, bool> $values
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
     * @return array<int, bool>
     */
    public function getValues()
    {
        return $this->values;
    }
}
