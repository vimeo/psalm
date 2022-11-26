<?php

namespace Psalm\Issue;

final class MixedPropertyAssignmentValue extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 314;

    use MixedIssueTrait;
}
