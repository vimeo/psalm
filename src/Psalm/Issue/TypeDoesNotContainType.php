<?php
namespace Psalm\Issue;

class TypeDoesNotContainType extends CodeIssue
{
    const ERROR_LEVEL = 4;
    const SHORTCODE = 56;

    public function __construct(string $message, \Psalm\CodeLocation $code_location, ?string $dupe_key)
    {
        parent::__construct($message, $code_location);
        $this->dupe_key = $dupe_key;
    }
}
