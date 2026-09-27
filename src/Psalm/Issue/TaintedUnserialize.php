<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @api
 */
final class TaintedUnserialize extends TaintedInput
{
    public const SHORTCODE = 250;
}
