<?php

namespace Psalm\Type;

use Psalm\Type;

class Atomic extends Type
{
    /** @var string */
    public $value;

    /**
     * Constructs an Atomic instance
     * @param string    $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
