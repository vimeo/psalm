<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

final class RiskyTruthyFalsyComparison extends CodeIssue
{
    public const ERROR_LEVEL = 2;
    public const SHORTCODE = 356;

    public function __construct(string $message, CodeLocation $code_location, ?string $dupe_key)
    {
        parent::__construct($message, $code_location);
        $this->dupe_key = $dupe_key;
    }
}
