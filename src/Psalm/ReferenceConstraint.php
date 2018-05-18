<?php
namespace Psalm;

class ReferenceConstraint
{
    /** @var Type\Union|null */
    public $type;

    /**
     * @param  Type\Union $type
     */
    public function __construct(Type\Union $type = null)
    {
        if ($type) {
            $this->type = clone $type;

            if ($this->type->getLiteralStrings()) {
                $this->type->addType(new Type\Atomic\TString);
            }

            if ($this->type->getLiteralInts()) {
                $this->type->addType(new Type\Atomic\TInt);
            }

            if ($this->type->getLiteralFloats()) {
                $this->type->addType(new Type\Atomic\TFloat);
            }
        }
    }
}
