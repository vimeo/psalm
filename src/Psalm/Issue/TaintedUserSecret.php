<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class TaintedUserSecret extends TaintedInput
{
    public const SHORTCODE = 247;
}
