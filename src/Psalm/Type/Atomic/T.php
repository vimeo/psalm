<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a string that we know holds some type information about another variable,
 * specified in the $typeof property
 */
abstract class T extends TString
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
