<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class UndefinedMagicPropertyAssignment extends PropertyIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 217;
}
