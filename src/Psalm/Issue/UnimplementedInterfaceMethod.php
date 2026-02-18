<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class UnimplementedInterfaceMethod extends CodeIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 44;
}
