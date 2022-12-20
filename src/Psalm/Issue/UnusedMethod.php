<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

use function strtolower;

final class UnusedMethod extends MethodIssue
{
    public const ERROR_LEVEL = -2;
    public const SHORTCODE = 76;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $method_id
    ) {
        parent::__construct($message, $code_location, $method_id);
        $this->dupe_key = strtolower($method_id);
    }
}
