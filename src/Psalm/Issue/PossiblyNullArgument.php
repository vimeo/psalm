<?php

declare(strict_types=1);

namespace Psalm\Issue;

class PossiblyNullArgument extends ArgumentIssue
{
    public const ERROR_LEVEL = 3;
    public const SHORTCODE = 78;
}
