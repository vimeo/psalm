<?php

namespace Psalm\Issue;

final class MixedStringOffsetAssignment extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 35;

    use MixedIssueTrait;
}
