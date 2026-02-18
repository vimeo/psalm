<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class PossiblyInvalidArgument extends ArgumentIssue
{
    public const ERROR_LEVEL = 3;
    public const SHORTCODE = 92;
}
