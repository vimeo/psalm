<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class WeakHash extends CodeIssue implements SecurityIssue
{
    public const ERROR_LEVEL = 2;
    public const SHORTCODE = 381;
    public const CWE_IDS = [328];
}
