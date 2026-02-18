<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class DeprecatedInterface extends ClassIssue
{
    public const ERROR_LEVEL = 2;
    public const SHORTCODE = 152;
}
