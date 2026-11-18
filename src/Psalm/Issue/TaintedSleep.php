<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedSleep extends TaintedInput
{
    public const SHORTCODE = 324;
    public const CWE_IDS = [400];
}
