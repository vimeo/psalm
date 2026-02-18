<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class TaintedLdap extends TaintedInput
{
    public const SHORTCODE = 254;
}
