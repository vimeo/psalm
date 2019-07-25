<?php
namespace Psalm\Issue;

use function strtolower;

abstract class ArgumentIssue extends CodeIssue
{
    /**
     * @var ?string
     */
    public $function_id;

    /**
     * @param string        $message
     * @param \Psalm\CodeLocation  $code_location
     * @param string        $function_id
     */
    public function __construct(
        $message,
        \Psalm\CodeLocation $code_location,
        string $function_id = null
    ) {
        parent::__construct($message, $code_location);
        $this->function_id = $function_id ? strtolower($function_id) : null;
    }
}
