<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class PossiblyNullReference extends CodeIssue
{
    public const ERROR_LEVEL = 3;
    public const SHORTCODE = 83;
}
