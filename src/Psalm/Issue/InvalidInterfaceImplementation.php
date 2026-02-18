<?php

declare(strict_types=1);

namespace Psalm\Issue;

/**
 * @psalm-immutable
 */
final class InvalidInterfaceImplementation extends ClassIssue
{
    final public const ERROR_LEVEL = -1;
    final public const SHORTCODE = 317;
}
