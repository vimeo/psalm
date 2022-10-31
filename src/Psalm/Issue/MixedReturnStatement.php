<?php

namespace Psalm\Issue;

final class MixedReturnStatement extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 138;

    use MixedIssueTrait;
}
