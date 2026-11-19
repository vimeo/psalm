<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class WeakRandom extends CodeIssue implements SecurityIssue
{
    public const ERROR_LEVEL = 2;
    public const SHORTCODE = 383;
    public const CWE_IDS = [335, 330];
}
