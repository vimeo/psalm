<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedLlmResponse extends TaintedInput
{
    public const SHORTCODE = 392;
    public const CWE_IDS = [1427];
}
