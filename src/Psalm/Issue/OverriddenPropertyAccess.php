<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class OverriddenPropertyAccess extends CodeIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 159;
}
