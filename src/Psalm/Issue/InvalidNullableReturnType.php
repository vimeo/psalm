<?php

declare(strict_types=1);

namespace Psalm\Issue;

class InvalidNullableReturnType extends CodeIssue
{
    public const ERROR_LEVEL = 5;
    public const SHORTCODE = 144;
}
