<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class TooFewArguments extends ArgumentIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 25;
}
