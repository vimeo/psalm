<?php

namespace Psalm\Issue;

final class MixedArrayOffset extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 31;

    use MixedIssueTrait;
}
