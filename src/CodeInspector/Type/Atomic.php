<?php

namespace CodeInspector\Type;

use CodeInspector\Type;

class Atomic extends Type
{
    /** @var string */
    public $value;

    /** @var boolean */
    public $negated = false;

    /**
     * Constructs an Atomic instance
     * @param string    $value
     * @param boolean   $negated
     */
    public function __construct($value, $negated = false)
    {
        $this->value = $value;
        $this->negated = $negated;
    }

    public function __toString()
    {
        return ($this->negated ? '!' : '') . $this->value;
    }
}
