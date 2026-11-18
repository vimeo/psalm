<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedSession extends TaintedInput
{
    public const SHORTCODE = 380;
    public const CWE_IDS = [501];
}
