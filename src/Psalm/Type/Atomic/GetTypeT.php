<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a string whose value is that of a type found by gettype($var)
 */
class GetTypeT extends TString
{
    /**
     * Used to hold information as to what this refers to
     *
     * @var string
     */
    public $typeof;

    /**
     * @param string $typeof the variable id
     */
    public function __construct($typeof)
    {
        $this->typeof = $typeof;
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
