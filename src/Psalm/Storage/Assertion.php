<?php
namespace Psalm\Storage;

class Assertion
{
    /**
     * @var string the rule being asserted
     */
    public $rule;

    /**
     * @var int|string the id of the property/variable, or
     *  the parameter offset of the affected arg
     */
    public $var_id;

    /**
     * @param string|int $var_id
     * @param string $rule
     */
    public function __construct($var_id, $rule)
    {
        $this->rule = $rule;
        $this->var_id = $var_id;
    }
}
