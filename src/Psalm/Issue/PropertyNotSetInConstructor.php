<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

final class PropertyNotSetInConstructor extends PropertyIssue
{
    public const ERROR_LEVEL = 2;
    public const SHORTCODE = 74;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $property_id
    ) {
        parent::__construct($message, $code_location, $property_id);
        $this->dupe_key = $property_id;
    }
}
