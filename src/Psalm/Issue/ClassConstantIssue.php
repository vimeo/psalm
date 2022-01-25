<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class ClassConstantIssue extends CodeIssue
{
    /**
     * @var string
     */
    public $const_id;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $const_id
    ) {
        parent::__construct($message, $code_location);
        $this->const_id = $const_id;
    }
}
