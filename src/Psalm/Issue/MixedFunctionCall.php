<?php

namespace Psalm\Issue;

final class MixedFunctionCall extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 185;

    use MixedIssueTrait;
}
