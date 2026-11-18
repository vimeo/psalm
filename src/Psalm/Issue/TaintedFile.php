<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedFile extends TaintedInput
{
    public const SHORTCODE = 255;
    public const CWE_IDS = [22, 73];
}
