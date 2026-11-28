<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedTimingComparison extends TaintedInput
{
    public const SHORTCODE = 393;
    public const CWE_IDS = [208];
}
