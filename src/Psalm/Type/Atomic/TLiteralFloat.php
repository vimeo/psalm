<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TLiteralFloat extends TFloat
{
    /** @var float */
    public $value;

    /**
     * @param float $value
     */
    public function __construct(float $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'float(' . $this->value . ')';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'float(' . $this->value . ')';
    }
}
