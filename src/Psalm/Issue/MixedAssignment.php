<?php

namespace Psalm\Issue;

final class MixedAssignment extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 32;

    use MixedIssueTrait;
}
