<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedRedirect extends TaintedInput
{
    public const SHORTCODE = 387;
    public const CWE_IDS = [601];
}
