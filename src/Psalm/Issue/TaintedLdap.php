<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedLdap extends TaintedInput
{
    public const SHORTCODE = 254;
    public const CWE_IDS = [90];
}
