<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class UnusedFunctionCall extends FunctionIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 206;
}
