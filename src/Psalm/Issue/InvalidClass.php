<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class InvalidClass extends ClassIssue
{
    public const ERROR_LEVEL = 6;
    public const SHORTCODE = 7;
}
