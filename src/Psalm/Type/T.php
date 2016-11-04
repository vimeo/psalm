<?php
namespace Psalm\Type;

class T extends Atomic
{
    /**
     * Used to hold information as to what this refers to
     * @var string
     */
    public $typeof;

    /**
     * @param string $typeof the variable id
     */
    public function __construct($typeof)
    {
        $this->value = 'string';

        $this->typeof = $typeof;
    }
}
