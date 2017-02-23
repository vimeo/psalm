<?php
namespace Psalm;

class ReferenceConstraint
{
    /** @var Type\Union */
    public $type;

    /**
     * @param  Type\Union $type
     */
    public function __construct(Type\Union $type)
    {
        $this->type = $type;
    }
}
