<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class InsecureCookie extends CodeIssue implements SecurityIssue
{
    public const ERROR_LEVEL = 3;
    public const SHORTCODE = 384;
    public const CWE_IDS = [614];
}
