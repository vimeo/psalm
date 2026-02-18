<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class TaintedUnserialize extends TaintedInput
{
    public const SHORTCODE = 250;
}
