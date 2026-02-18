<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class UndefinedInterface extends ClassIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 189;
}
