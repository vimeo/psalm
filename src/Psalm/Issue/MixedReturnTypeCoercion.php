<?php

namespace Psalm\Issue;

final class MixedReturnTypeCoercion extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 197;

    use MixedIssueTrait;
}
