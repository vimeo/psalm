<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class ReservedWord extends ClassIssue
{
    public const ERROR_LEVEL = 7;
    public const SHORTCODE = 95;
}
