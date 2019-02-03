<?php

namespace Psalm\Type\Atomic;

interface HasClassString
{
    public function hasSingleNamedObject() : bool;

    public function getSingleNamedObject() : TNamedObject;
}
