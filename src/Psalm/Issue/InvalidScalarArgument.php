<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class InvalidScalarArgument extends ArgumentIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 12;
}
