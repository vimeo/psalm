<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class PropertyIssue extends CodeIssue
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        public string $property_id,
    ) {
        parent::__construct($message, $code_location);
    }
}
