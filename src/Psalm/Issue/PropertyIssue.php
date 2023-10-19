<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class PropertyIssue extends CodeIssue
{
    public string $property_id;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $property_id,
    ) {
        parent::__construct($message, $code_location);
        $this->property_id = $property_id;
    }
}
