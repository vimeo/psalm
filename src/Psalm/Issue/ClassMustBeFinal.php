<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class ClassMustBeFinal extends ClassIssue
{
    public const ERROR_LEVEL = 2;
    public const SHORTCODE = 361;
}
