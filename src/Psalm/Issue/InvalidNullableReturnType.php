<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class InvalidNullableReturnType extends CodeIssue
{
    public const ERROR_LEVEL = 5;
    public const SHORTCODE = 144;
}
