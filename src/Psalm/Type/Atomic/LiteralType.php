<?php
namespace Psalm\Type\Atomic;

interface LiteralType
{
    /**
     * @return array<string|int, bool>
     */
    public function getValues();
}
