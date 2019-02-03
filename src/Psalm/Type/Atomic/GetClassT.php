<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

/**
 * Represents a string whose value is a fully-qualified class found by get_class($var)
 */
class GetClassT extends TString implements HasClassString
{
    /**
     * Used to hold information as to what this refers to
     *
     * @var string
     */
    public $typeof;

    /**
     * @var Union
     */
    public $as_type;

    /**
     * @param string $typeof the variable id
     */
    public function __construct($typeof, Union $as_type)
    {
        $this->typeof = $typeof;
        $this->as_type = $as_type;
    }

    public function getId()
    {
        return 'class-string';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }

    public function hasSingleNamedObject() : bool
    {
        return $this->as_type->isSingle() && $this->as_type->hasNamedObject();
    }

    public function getSingleNamedObject() : TNamedObject
    {
        $first_value = array_values($this->as_type->getTypes())[0];

        if (!$first_value instanceof TNamedObject) {
            throw new \UnexpectedValueException('Bad object');
        }

        return $first_value;
    }
}
