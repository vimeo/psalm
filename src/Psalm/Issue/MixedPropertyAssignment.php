<?php

namespace Psalm\Issue;

final class MixedPropertyAssignment extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 33;

    use MixedIssueTrait;
}
