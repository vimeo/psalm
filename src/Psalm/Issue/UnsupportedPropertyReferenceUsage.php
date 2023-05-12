<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

final class UnsupportedPropertyReferenceUsage extends CodeIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 321;

    public function __construct(CodeLocation $code_location)
    {
        parent::__construct(
            'This reference cannot be analyzed by Psalm.',
            $code_location,
        );
    }
}
