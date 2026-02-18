<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class InvalidIterator extends CodeIssue
{
    public const ERROR_LEVEL = 6;
    public const SHORTCODE = 9;
}
