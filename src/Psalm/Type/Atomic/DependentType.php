<?php
namespace Psalm\Type\Atomic;

interface DependentType
{
    public function getVarId() : string;

    /**
     * This returns a replacement type for when the dependent data is invalidated
     */
    public function getReplacement() : \Psalm\Type\Atomic;
}
