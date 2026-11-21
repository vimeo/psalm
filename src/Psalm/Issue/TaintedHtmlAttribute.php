<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedHtmlAttribute extends TaintedInput
{
    public const SHORTCODE = 388;
    public const CWE_IDS = [79];
}
