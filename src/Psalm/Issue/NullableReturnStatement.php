<?php

declare(strict_types=1);

namespace Psalm\Issue;

final class NullableReturnStatement extends CodeIssue
{
    public const ERROR_LEVEL = 5;
    public const SHORTCODE = 139;
}
