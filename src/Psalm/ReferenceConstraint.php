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
        $this->type = $type;
    }
}
