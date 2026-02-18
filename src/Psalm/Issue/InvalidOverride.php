<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class InvalidOverride extends CodeIssue
{
    public const ERROR_LEVEL = 7;
    public const SHORTCODE = 357;
}
