<?php
namespace Psalm\Issue;

use function strtolower;

abstract class VariableIssue extends CodeIssue
{
    /**
     * @var string
     */
    public $var_name;

    /**
     * @param string $message
     * @param \Psalm\CodeLocation $code_location
     * @param string $var_name
     */
    public function __construct(
        $message,
        \Psalm\CodeLocation $code_location,
        $var_name
    ) {
        parent::__construct($message, $code_location);
        $this->var_name = strtolower($var_name);
    }
}
