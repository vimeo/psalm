<?php

namespace Psalm\Issue;

final class MixedArrayTypeCoercion extends CodeIssue implements MixedIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 195;

    use MixedIssueTrait;
}
