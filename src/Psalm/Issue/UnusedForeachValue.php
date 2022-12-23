<?php

declare(strict_types=1);

namespace Psalm\Issue;

final class UnusedForeachValue extends CodeIssue
{
    public const ERROR_LEVEL = -2;
    public const SHORTCODE = 275;
}
