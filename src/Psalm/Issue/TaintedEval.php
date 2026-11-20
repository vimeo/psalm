<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedEval extends TaintedInput
{
    public const SHORTCODE = 252;
    public const CWE_IDS = [95, 94];
}
