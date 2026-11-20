<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedCookie extends TaintedInput
{
    public const SHORTCODE = 257;
    public const CWE_IDS = [384];
}
