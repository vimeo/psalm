<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class ClassConstantIssue extends CodeIssue
{
    public function __construct(
        string $message,
        CodeLocation $code_location,
        public string $const_id,
    ) {
        parent::__construct($message, $code_location);
    }
}
