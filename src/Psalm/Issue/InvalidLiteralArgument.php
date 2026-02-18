<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class InvalidLiteralArgument extends ArgumentIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 237;
}
