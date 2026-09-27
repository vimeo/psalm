<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedHeader extends TaintedInput
{
    public const SHORTCODE = 256;
}
