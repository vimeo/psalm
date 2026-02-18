<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class UnusedMethodCall extends MethodIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 209;
}
