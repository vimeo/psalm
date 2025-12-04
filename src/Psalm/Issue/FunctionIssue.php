<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class FunctionIssue extends CodeIssue
{
    public function __construct(
        string $message,
        CodeLocation $code_location,
        /** @var lowercase-string */
        public readonly string $function_id,
    ) {
        parent::__construct($message, $code_location);
    }
}
