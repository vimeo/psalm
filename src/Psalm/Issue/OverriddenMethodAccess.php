<?php

declare(strict_types=1);

namespace Psalm\Issue;

class OverriddenMethodAccess extends CodeIssue
{
    public const ERROR_LEVEL = 7;
    public const SHORTCODE = 66;
}
