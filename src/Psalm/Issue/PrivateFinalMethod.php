<?php

declare(strict_types=1);

namespace Psalm\Issue;

final class PrivateFinalMethod extends MethodIssue
{
    final public const ERROR_LEVEL = 2;
    final public const SHORTCODE = 320;
}
