<?php

namespace Psalm\Issue;

final class MixedInferredReturnType extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 47;

    use MixedIssueTrait;
}
