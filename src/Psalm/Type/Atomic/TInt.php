<?php
namespace Psalm\Type\Atomic;

class TInt extends Scalar
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
        return 'int';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'int';
    }
}
