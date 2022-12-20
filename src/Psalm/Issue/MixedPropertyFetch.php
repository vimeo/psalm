<?php

namespace Psalm\Issue;

final class MixedPropertyFetch extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 34;

    use MixedIssueTrait;
}
