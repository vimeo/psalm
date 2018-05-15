<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TLiteralInt extends TInt
{
    /** @var int */
    public $value;

    /**
     * @param int $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'int(' . $this->value . ')';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'int(' . $this->value . ')';
    }
}
