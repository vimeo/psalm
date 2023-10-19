<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

use function strtolower;

abstract class FunctionIssue extends CodeIssue
{
    public string $function_id;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $function_id,
    ) {
        parent::__construct($message, $code_location);
        $this->function_id = strtolower($function_id);
    }
}
