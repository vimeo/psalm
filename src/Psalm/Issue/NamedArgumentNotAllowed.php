<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class NamedArgumentNotAllowed extends ArgumentIssue
{
    public const ERROR_LEVEL = 7;
    public const SHORTCODE = 268;
}
