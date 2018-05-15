<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TLiteralString extends TString
{
    /** @var string */
    public $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'string(' . $this->value . ')';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'string(' . $this->value . ')';
    }
}
