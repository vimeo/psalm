<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class UndefinedMethod extends MethodIssue
{
    public const ERROR_LEVEL = 6;
    public const SHORTCODE = 22;
}
