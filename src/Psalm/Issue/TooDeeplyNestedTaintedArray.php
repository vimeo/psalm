<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TooDeeplyNestedTaintedArray extends CodeIssue
{
    public const ERROR_LEVEL = -2;
    public const SHORTCODE = 371;
}
