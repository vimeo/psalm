<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class ClassIssue extends CodeIssue
{
    public function __construct(
        string $message,
        CodeLocation $code_location,
        public string $fq_classlike_name,
    ) {
        parent::__construct($message, $code_location);
    }
}
