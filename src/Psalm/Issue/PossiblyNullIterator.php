<?php

declare(strict_types=1);

namespace Psalm\Issue;

class PossiblyNullIterator extends CodeIssue
{
    public const ERROR_LEVEL = 3;
    public const SHORTCODE = 97;
}
