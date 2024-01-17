<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

use function strtolower;

abstract class ArgumentIssue extends CodeIssue
{
    public ?string $function_id = null;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        ?string $function_id = null,
    ) {
        parent::__construct($message, $code_location);
        $this->function_id = $function_id ? strtolower($function_id) : null;
    }
}
