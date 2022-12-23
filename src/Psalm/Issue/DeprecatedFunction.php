<?php

declare(strict_types=1);

namespace Psalm\Issue;

final class DeprecatedFunction extends FunctionIssue
{
    public const ERROR_LEVEL = 2;
    public const SHORTCODE = 201;
}
