<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

use function strtolower;

abstract class MethodIssue extends CodeIssue
{
    public string $method_id;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $method_id,
    ) {
        parent::__construct($message, $code_location);
        $this->method_id = strtolower($method_id);
    }
}
