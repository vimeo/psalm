<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedUrlScheme extends TaintedInput
{
    public const SHORTCODE = 389;
    public const CWE_IDS = [79];
}
