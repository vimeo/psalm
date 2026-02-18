<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class UndefinedMagicPropertyFetch extends PropertyIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 218;
}
