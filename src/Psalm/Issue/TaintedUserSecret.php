<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedUserSecret extends TaintedInput
{
    public const SHORTCODE = 247;
    public const CWE_IDS = [200];
}
