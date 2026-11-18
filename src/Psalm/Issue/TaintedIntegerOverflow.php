<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedIntegerOverflow extends TaintedInput
{
    public const SHORTCODE = 386;
    public const CWE_IDS = [190];
}
