<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class MismatchingDocblockReturnType extends CodeIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 142;
}
