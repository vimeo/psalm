<?php
namespace Psalm\Issue;

abstract class MethodIssue extends CodeIssue
{
    /**
     * @var string
     */
    public $method_id;

    /**
     * @param string        $message
     * @param \Psalm\CodeLocation  $code_location
     * @param string        $method_id
     */
    public function __construct(
        $message,
        \Psalm\CodeLocation $code_location,
        $method_id
    ) {
        parent::__construct($message, $code_location);
        $this->method_id = strtolower($method_id);
    }
}
